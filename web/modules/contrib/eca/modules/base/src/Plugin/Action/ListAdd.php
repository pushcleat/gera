<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ListAddBase;

/**
 * Action to add an item to a list.
 *
 * @Action(
 *   id = "eca_list_add",
 *   label = @Translation("List: add item"),
 *   description = @Translation("Add an item to a list using a specified token.")
 * )
 */
class ListAdd extends ListAddBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $value = $this->tokenServices->getOrReplace($this->configuration['value']);
    $this->addItem($value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value to add'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $this->configuration['value'],
      '#weight' => 20,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['value'] = $form_state->getValue('value');
  }

}
