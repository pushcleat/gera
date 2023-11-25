<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca_base\BaseEvents;
use Drupal\eca_base\Event\CustomEvent;

/**
 * Trigger a custom event.
 *
 * @Action(
 *   id = "eca_trigger_custom_event",
 *   label = @Translation("Trigger a custom event")
 * )
 */
class TriggerCustomEvent extends ConfigurableActionBase {

  /**
   * Overrides \Drupal\eca\Plugin\ActionActionInterface::EXTERNALLY_AVAILABLE.
   *
   * @var bool
   */
  public const EXTERNALLY_AVAILABLE = TRUE;

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $event_id = $this->tokenServices->replaceClear($this->configuration['event_id']);
    $event = new CustomEvent($event_id, ['event' => $this->event]);
    $event->addTokenNamesFromString($this->configuration['tokens']);
    \Drupal::service('event_dispatcher')->dispatch($event, BaseEvents::CUSTOM);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'event_id' => '',
      'tokens' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event ID'),
      '#default_value' => $this->configuration['event_id'],
      '#weight' => -20,
      '#description' => $this->t('The ID of the event to be triggered.'),
    ];
    $form['tokens'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tokens to forward'),
      '#default_value' => $this->configuration['tokens'],
      '#description' => $this->t('Comma separated list of token names from the current context, that will be forwarded to the triggered event. These tokens are then also available for subsequent conditions and actions within the current process.'),
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['event_id'] = $form_state->getValue('event_id');
    $this->configuration['tokens'] = $form_state->getValue('tokens');
    parent::submitConfigurationForm($form, $form_state);
  }

}
