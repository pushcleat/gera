<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;

/**
 * Add a field with options to a form.
 *
 * @Action(
 *   id = "eca_form_add_optionsfield",
 *   label = @Translation("Form: add options field"),
 *   description = @Translation("Add a field with options as radios, checkboxes or select dropdown to the current form in scope."),
 *   type = "form"
 * )
 */
class FormAddOptionsfield extends FormAddFieldActionBase {

  use FormFieldSetOptionsTrait {
    defaultConfiguration as setOptionsDefaultconfiguration;
    buildConfigurationForm as setOptionsBuildConfigurationForm;
    submitConfigurationForm as setOptionsSubmitConfigurationForm;
    execute as setOptionsExecute;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldElement(): array {
    $element = parent::buildFieldElement();
    // Options will be filled up within ::execute().
    $element['#options'] = [];
    $is_multiple = (bool) $this->configuration['multiple'];
    $element['#multiple'] = $is_multiple;
    if (!$is_multiple && $element['#type'] === 'checkboxes') {
      $element['#type'] = 'radios';
    }
    elseif ($is_multiple && $element['#type'] === 'radios') {
      $element['#type'] = 'checkboxes';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'type' => 'select',
      'multiple' => TRUE,
    ] + $this->setOptionsDefaultconfiguration() + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTypeOptions(): array {
    $type_options = [
      'checkboxes' => $this->t('Checkboxes / radio buttons'),
      'select' => $this->t('Dropdown selection'),
    ];
    if ($this->moduleHandler->moduleExists('select2')) {
      $type_options['select2'] = $this->t('Select2 dropdown');
    }
    return $type_options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = $this->setOptionsBuildConfigurationForm($form, $form_state);
    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#description' => $this->t('Whether the user can select more than one value in the option field.'),
      '#default_value' => $this->configuration['multiple'],
      '#weight' => -45,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['multiple'] = !empty($form_state->getValue('multiple'));
    $this->setOptionsSubmitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    parent::execute();
    $this->setOptionsExecute();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    if ($this->configuration['type'] === 'select2') {
      $dependencies['module'][] = 'select2';
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDefaultValue() {
    if ($default_options = $this->buildOptionsArray($this->configuration['default_value'])) {
      $is_multiple = (bool) $this->configuration['multiple'];
      return $is_multiple ? array_keys($default_options) : key($default_options);
    }
    return parent::buildDefaultValue();
  }

}
