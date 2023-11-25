<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Flag the entity for creating a new revision.
 *
 * @Action(
 *   id = "eca_set_new_revision",
 *   label = @Translation("Entity: set new revision"),
 *   description = @Translation("Flags the entity so that a new revision will be created on the next save."),
 *   type = "entity"
 * )
 */
class SetNewRevision extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'new_revision' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $this->configuration['new_revision'],
      '#description' => $this->t('Whether to create a new revision or not'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['new_revision'] = !empty($form_state->getValue('new_revision'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!($object instanceof AccessibleInterface)) {
      $result = AccessResult::forbidden();
      return $return_as_object ? $result : $result->isAllowed();
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $object;
    $entity_op = 'update';

    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $entity->access($entity_op, $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if (!($entity instanceof RevisionableInterface)) {
      return;
    }
    $new_revision = $this->configuration['new_revision'];
    $entity->setNewRevision($new_revision);
  }

}
