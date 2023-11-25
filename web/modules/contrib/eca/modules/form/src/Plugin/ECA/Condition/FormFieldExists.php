<?php

namespace Drupal\eca_form\Plugin\ECA\Condition;

/**
 * Checks whether the current form contains a specific form field.
 *
 * @EcaCondition(
 *   id = "eca_form_field_exists",
 *   label = @Translation("Form field: exists"),
 *   description = @Translation("Looks up the current form structure whether a specified field exists.")
 * )
 */
class FormFieldExists extends FormFieldConditionBase {

  /**
   * Whether to use form field value filters or not.
   *
   * @var bool
   */
  protected bool $useFilters = FALSE;

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    if (!$this->getCurrentFormState()) {
      return FALSE;
    }
    return $this->negationCheck(!empty($this->getTargetElement()));
  }

}
