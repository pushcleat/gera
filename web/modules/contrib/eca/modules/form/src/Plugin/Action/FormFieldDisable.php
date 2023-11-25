<?php

namespace Drupal\eca_form\Plugin\Action;

/**
 * Set a form field as disabled.
 *
 * @Action(
 *   id = "eca_form_field_disable",
 *   label = @Translation("Form field: set as disabled"),
 *   description = @Translation("Disable a form field."),
 *   type = "form"
 * )
 */
class FormFieldDisable extends FormFlagFieldActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFlagName(bool $human_readable = FALSE) {
    return $human_readable ? $this->t('disabled') : 'disabled';
  }

  /**
   * {@inheritdoc}
   */
  protected function flagAllChildren(&$element, bool $flag): void {
    parent::flagAllChildren($element, $flag);
    $this->setFormFieldAttributes($element);
  }

  /**
   * Set form field attributes on the given element.
   *
   * Sometimes it is too late that the form builder sets proper HTML attributes.
   * Therefore, this helper method assures they are set.
   * @see \Drupal\Core\Form\FormBuilder::handleInputElement
   *
   * @param array &$element
   *   The form element.
   */
  protected function setFormFieldAttributes(array &$element): void {
    if (empty($element['#input'])) {
      return;
    }
    if (!empty($element['#allow_focus'])) {
      $element['#attributes']['readonly'] = 'readonly';
    }
    else {
      $element['#attributes']['disabled'] = 'disabled';
    }
  }

}
