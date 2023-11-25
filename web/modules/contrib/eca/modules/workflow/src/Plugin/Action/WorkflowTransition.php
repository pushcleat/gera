<?php

namespace Drupal\eca_workflow\Plugin\Action;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform a workflow transition on an entity.
 *
 * @Action(
 *   id = "eca_workflow_transition",
 *   type = "entity",
 *   deriver = "Drupal\eca_workflow\Plugin\Action\WorkflowTransitionDeriver"
 * )
 */
class WorkflowTransition extends ConfigurableActionBase {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected ModerationInformationInterface $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_workflow\Plugin\Action\WorkflowTransition $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setModerationInformation($container->get('content_moderation.moderation_information'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->createRevision($entity, $entity->isDefaultRevision());
    $entity->set('moderation_state', $this->configuration['new_state']);
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionLogMessage($this->configuration['revision_log']);
      $entity->setRevisionUserId($this->currentUser->id());
    }
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::forbidden();
    if (($object instanceof ContentEntityInterface) && ($workflow = $this->moderationInformation->getWorkflowForEntity($object))) {
      $current_state = $object->moderation_state->value;
      $workflowPlugin = $workflow->getTypePlugin();
      if ($workflowPlugin->hasState($current_state) && $workflowPlugin->getState($current_state)->canTransitionTo($this->configuration['new_state'])) {
        $result = AccessResult::allowed();
      }
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'new_state' => '',
      'revision_log' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = [];
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load($this->getPluginDefinition()['workflow_id']);
    foreach ($workflow->getTypePlugin()->getStates() as $state) {
      $options[$state->id()] = $state->label();
    }
    $form['new_state'] = [
      '#type' => 'select',
      '#title' => $this->t('New state'),
      '#options' => $options,
      '#default_value' => $this->configuration['new_state'],
      '#weight' => -20,
    ];
    $form['revision_log'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision Log'),
      '#default_value' => $this->configuration['revision_log'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['new_state'] = $form_state->getValue('new_state');
    $this->configuration['revision_log'] = $form_state->getValue('revision_log');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Set the moderation information service.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function setModerationInformation(ModerationInformationInterface $moderation_information): void {
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [
      'config' => [
        'workflows.workflow.' . $this->pluginDefinition['workflow_id'],
      ],
    ];
  }

}
