<?php

namespace Drupal\eca_endpoint\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Set the response content type header.
 *
 * @Action(
 *   id = "eca_endpoint_set_response_content_type",
 *   label = @Translation("Response: set content type")
 * )
 */
class SetResponseContentType extends ResponseActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(): void {
    $content_type = (string) $this->tokenServices->replaceClear($this->configuration['content_type']);
    $this->getResponse()->headers->set('Content-Type', $content_type);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'content_type' => 'text/html; charset=UTF-8',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['content_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('content_type'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $this->configuration['content_type'],
      '#weight' => -20,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['content_type'] = (string) $form_state->getValue('content_type');
    parent::submitConfigurationForm($form, $form_state);
  }

}
