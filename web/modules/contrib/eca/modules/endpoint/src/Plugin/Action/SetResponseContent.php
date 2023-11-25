<?php

namespace Drupal\eca_endpoint\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Set the response content.
 *
 * @Action(
 *   id = "eca_endpoint_set_response_content",
 *   label = @Translation("Response: set content")
 * )
 */
class SetResponseContent extends ResponseActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(): void {
    $content = (string) $this->tokenServices->replaceClear($this->configuration['content']);
    $this->getResponse()->setContent($content);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'content' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#description' => $this->t('The response content to set. This field supports tokens.'),
      '#default_value' => $this->configuration['content'],
      '#weight' => -20,
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['content'] = (string) $form_state->getValue('content');
    parent::submitConfigurationForm($form, $form_state);
  }

}
