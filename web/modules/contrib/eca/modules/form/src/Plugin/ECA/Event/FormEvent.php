<?php

namespace Drupal\eca_form\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_form\Event\FormAfterBuild;
use Drupal\eca_form\Event\FormBuild;
use Drupal\eca_form\Event\FormEvents;
use Drupal\eca_form\Event\FormProcess;
use Drupal\eca_form\Event\FormSubmit;
use Drupal\eca_form\Event\FormValidate;

/**
 * Plugin implementation of the ECA Events for the form API.
 *
 * @EcaEvent(
 *   id = "form",
 *   deriver = "Drupal\eca_form\Plugin\ECA\Event\FormEventDeriver"
 * )
 */
class FormEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];
    $actions['form_build'] = [
      'label' => 'Build form',
      'event_name' => FormEvents::BUILD,
      'event_class' => FormBuild::class,
      'tags' => Tag::VIEW | Tag::RUNTIME | Tag::BEFORE,
    ];
    $actions['form_process'] = [
      'label' => 'Process form',
      'event_name' => FormEvents::PROCESS,
      'event_class' => FormProcess::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_after_build'] = [
      'label' => 'After build form',
      'event_name' => FormEvents::AFTER_BUILD,
      'event_class' => FormAfterBuild::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_validate'] = [
      'label' => 'Validate form',
      'event_name' => FormEvents::VALIDATE,
      'event_class' => FormValidate::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_submit'] = [
      'label' => 'Submit form',
      'event_name' => FormEvents::SUBMIT,
      'event_class' => FormSubmit::class,
      'tags' => Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'form_id' => '',
      'entity_type_id' => '',
      'bundle' => '',
      'operation' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restrict by form ID'),
      '#default_value' => $this->configuration['form_id'],
      '#description' => $this->t('The form ID can be mostly found in the HTML &lt;form&gt; element as "id" attribute.'),
    ];
    $form['entity_type_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restrict by entity type ID'),
      '#default_value' => $this->configuration['entity_type_id'],
      '#description' => $this->t('Example: <em>node, taxonomy_term, user</em>'),
    ];
    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restrict by entity bundle'),
      '#default_value' => $this->configuration['bundle'],
      '#description' => $this->t('Example: <em>article, tags</em>'),
    ];
    $form['operation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restrict by operation'),
      '#default_value' => $this->configuration['operation'],
      '#description' => $this->t('Example: <em>default, save, delete</em>'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['form_id'] = $form_state->getValue('form_id');
    $this->configuration['entity_type_id'] = $form_state->getValue('entity_type_id');
    $this->configuration['bundle'] = $form_state->getValue('bundle');
    $this->configuration['operation'] = $form_state->getValue('operation');
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();

    $wildcard = '';
    $form_ids = [];
    if (!empty($configuration['form_id'])) {
      foreach (explode(',', $configuration['form_id']) as $form_id) {
        $form_id = strtolower(trim(str_replace('-', '_', $form_id)));
        if ($form_id !== '') {
          $form_ids[] = $form_id;
        }
      }
    }
    if ($form_ids) {
      $wildcard .= implode(',', $form_ids);
    }
    else {
      $wildcard .= '*';
    }

    $wildcard .= ':';
    $entity_type_ids = [];
    if (!empty($configuration['entity_type_id'])) {
      foreach (explode(',', $configuration['entity_type_id']) as $entity_type_id) {
        $entity_type_id = strtolower(trim($entity_type_id));
        if ($entity_type_id !== '') {
          $entity_type_ids[] = $entity_type_id;
        }
      }
    }
    if ($entity_type_ids) {
      $wildcard .= implode(',', $entity_type_ids);
    }
    else {
      $wildcard .= '*';
    }

    $wildcard .= ':';
    $bundles = [];
    if (!empty($configuration['bundle'])) {
      foreach (explode(',', $configuration['bundle']) as $bundle) {
        $bundle = strtolower(trim($bundle));
        if ($bundle !== '') {
          $bundles[] = $bundle;
        }
      }
    }
    if ($bundles) {
      $wildcard .= implode(',', $bundles);
    }
    else {
      $wildcard .= '*';
    }

    $wildcard .= ':';
    $operations = [];
    if (!empty($configuration['operation'])) {
      foreach (explode(',', $configuration['operation']) as $operation) {
        $operation = trim($operation);
        if ($operation !== '') {
          $operations[] = $operation;
        }
      }
    }
    if ($operations) {
      $wildcard .= implode(',', $operations);
    }
    else {
      $wildcard .= '*';
    }

    return $wildcard;
  }

}
