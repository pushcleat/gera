<?php

namespace Drupal\eca_endpoint\Plugin\Action;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Set the response expires.
 *
 * @Action(
 *   id = "eca_endpoint_set_response_expires",
 *   label = @Translation("Response: set expires")
 * )
 */
class SetResponseExpires extends ResponseActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(): void {
    $expires = $this->tokenServices->replaceClear($this->configuration['expires']);
    $expires = ctype_digit($expires) ? new DrupalDateTime("@$expires") : new DrupalDateTime($expires, new \DateTimeZone('UTC'));
    $this->getResponse()->setExpires($expires->getPhpDateTime());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'expires' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['expires'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expires'),
      '#description' => $this->t('The date of expiry, either formatted as a date time string, or a UNIX timestamp. This field supports tokens.'),
      '#default_value' => $this->configuration['expires'],
      '#weight' => -20,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['expires'] = (string) $form_state->getValue('expires');
    parent::submitConfigurationForm($form, $form_state);
  }

}
