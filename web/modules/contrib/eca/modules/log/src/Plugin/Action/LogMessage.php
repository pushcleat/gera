<?php

namespace Drupal\eca_log\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Write a log message.
 *
 * @Action(
 *   id = "eca_write_log_message",
 *   label = @Translation("Log Message"),
 *   description = @Translation("Writes a log message into the given type with the given severity.")
 * )
 */
class LogMessage extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $channel = $this->tokenServices->replaceClear($this->configuration['channel']);
    if (empty($channel)) {
      $channel = 'eca';
    }
    $severity = (int) $this->configuration['severity'];
    $message = $this->configuration['message'];
    $context = [];
    foreach ($this->tokenServices->scan($message) as $type => $tokens) {
      $replacements = $this->tokenServices->generate($type, $tokens, [], ['clear' => TRUE], new BubbleableMetadata());
      foreach ($replacements as $original_token => $replacement_value) {
        $context_argument = '%token__' . mb_substr(str_replace(':', '_', $original_token), 1, -1);
        $message = str_replace($original_token, $context_argument, $message);
        $context[$context_argument] = $replacement_value;
      }
    }
    \Drupal::logger($channel)->log($severity, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'channel' => '',
      'severity' => '',
      'message' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['channel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type'),
      '#default_value' => $this->configuration['channel'],
      '#description' => $this->t('The name of the logger type, the message should be logged to.'),
      '#weight' => -30,
    ];
    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#description' => $this->t('The severity of the log message.'),
      '#default_value' => $this->configuration['severity'],
      '#options' => RfcLogLevel::getLevels(),
      '#weight' => -20,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('The message, which should be logged.'),
      '#default_value' => $this->configuration['message'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['channel'] = $form_state->getValue('channel');
    $this->configuration['severity'] = $form_state->getValue('severity');
    $this->configuration['message'] = $form_state->getValue('message');
    parent::submitConfigurationForm($form, $form_state);
  }

}
