<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Set the action URL to use when submitting the form.
 *
 * @Action(
 *   id = "eca_form_set_action",
 *   label = @Translation("Form: set action"),
 *   description = @Translation("Set the action URL to use when submitting the form."),
 *   type = "form"
 * )
 */
class FormSetAction extends FormActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (!($form = &$this->getCurrentForm())) {
      return;
    }
    $form['#action'] = trim((string) $this->tokenServices->replaceClear($this->configuration['action']));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'action' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action URL'),
      '#description' => $this->t('The URL of a form action like <em>www.example.com/search</em>. This field supports tokens.'),
      '#weight' => -10,
      '#default_value' => $this->configuration['action'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['action'] = $form_state->getValue('action');
  }

}
