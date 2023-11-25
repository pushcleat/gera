<?php

namespace Drupal\eca_form\Plugin\Action;

/**
 * Set a form field as required.
 *
 * @Action(
 *   id = "eca_form_field_require",
 *   label = @Translation("Form field: set as required"),
 *   description = @Translation("Set a form field as required."),
 *   type = "form"
 * )
 */
class FormFieldRequire extends FormFlagFieldActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFlagName(bool $human_readable = FALSE) {
    return $human_readable ? $this->t('required') : 'required';
  }

}
