<?php

namespace Drupal\eca_form\Plugin\Action;

/**
 * Set access to a form field.
 *
 * @Action(
 *   id = "eca_form_field_access",
 *   label = @Translation("Form field: set access"),
 *   description = @Translation("Set access to a form field."),
 *   type = "form"
 * )
 */
class FormFieldAccess extends FormFlagFieldActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFlagName(bool $human_readable = FALSE) {
    return $human_readable ? $this->t('access') : 'access';
  }

}
