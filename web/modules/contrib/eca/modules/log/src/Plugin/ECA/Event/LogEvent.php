<?php

namespace Drupal\eca_log\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_log\Event\LogMessageEvent;
use Drupal\eca_log\LogEvents;

/**
 * Plugin implementation of the ECA Events for log messages.
 *
 * @EcaEvent(
 *   id = "log",
 *   deriver = "Drupal\eca_log\Plugin\ECA\Event\LogEventDeriver"
 * )
 */
class LogEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];
    $actions['log_message'] = [
      'label' => 'Log message created',
      'event_name' => LogEvents::MESSAGE,
      'event_class' => LogMessageEvent::class,
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    if ($this->eventClass() === LogMessageEvent::class) {
      $values = [
        'channel' => '',
        'min_severity' => '',
      ];
    }
    else {
      $values = [];
    }
    return $values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    if ($this->eventClass() === LogMessageEvent::class) {
      $form['channel'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Type'),
        '#description' => $this->t('The name of the logger type.'),
        '#default_value' => $this->configuration['channel'],
      ];
      $form['min_severity'] = [
        '#type' => 'select',
        '#title' => $this->t('Minimum severity'),
        '#description' => $this->t('The minimum severity. E.g. "critical" also covers "alert" and below.'),
        '#options' => RfcLogLevel::getLevels(),
        '#default_value' => $this->configuration['min_severity'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    if ($this->eventClass() === LogMessageEvent::class) {
      $this->configuration['channel'] = $form_state->getValue('channel');
      $this->configuration['min_severity'] = $form_state->getValue('min_severity');
    }
  }

}
