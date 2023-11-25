<?php

namespace Drupal\eca_queue\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Enqueue a Task with a delay.
 *
 * @Action(
 *   id = "eca_enqueue_task_delayed",
 *   label = @Translation("Enqueue a task with a delay")
 * )
 */
class EnqueueTaskDelayed extends EnqueueTask {

  public const DELAY_SECONDS = 1;
  public const DELAY_MINUTES = 60;
  public const DELAY_HOURS = 3600;
  public const DELAY_DAYS = 86400;
  public const DELAY_WEEKS = 604800;
  public const DELAY_MONTHS = 2592000;

  /**
   * {@inheritdoc}
   */
  protected function getEarliestProcessingTime(): int {
    return \Drupal::time()->getCurrentTime() +
      (int) $this->tokenServices->replaceClear($this->configuration['delay_value']) * (int) $this->configuration['delay_unit'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'delay_value' => '1',
      'delay_unit' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['delay_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay value'),
      '#default_value' => $this->configuration['delay_value'],
      '#weight' => -20,
    ];
    $form['delay_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Delay unit'),
      '#default_value' => $this->configuration['delay_unit'],
      '#options' => [
        static::DELAY_SECONDS => $this->t('seconds'),
        static::DELAY_MINUTES => $this->t('minutes'),
        static::DELAY_HOURS => $this->t('hours'),
        static::DELAY_DAYS => $this->t('days'),
        static::DELAY_WEEKS => $this->t('weeks'),
        static::DELAY_MONTHS => $this->t('months'),
      ],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['delay_value'] = $form_state->getValue('delay_value');
    $this->configuration['delay_unit'] = $form_state->getValue('delay_unit');
    parent::submitConfigurationForm($form, $form_state);
  }

}
