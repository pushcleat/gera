<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Action to show a validation error message.
 *
 * @Action(
 *   id = "eca_form_field_set_error",
 *   label = @Translation("Form field: set validation error"),
 *   description = @Translation("Shows a validation error with a given message text."),
 *   type = "form"
 * )
 */
class FormFieldSetError extends FormFieldValidateActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'message' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('The error message to be shown regards the form field. Supports tokens.'),
      '#default_value' => $this->configuration['message'],
      '#weight' => -49,
    ];
    $form['field_name']['#description'] .= ' ' . $this->t("Leave empty to set a global error on the form.");
    $form['field_name']['#required'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['message'] = $form_state->getValue('message');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (trim((string) $this->configuration['field_name']) === '') {
      // We support setting a global error on the whole form.
      $this->doExecute();
    }
    else {
      // Otherwise, let the parent logic execute.
      parent::execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(): void {
    $this->setError($this->tokenServices->replaceClear($this->configuration['message']));
  }

}
