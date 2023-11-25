<?php

namespace Drupal\eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;

/**
 * Base class for actions removing an item from a list.
 */
abstract class ListRemoveBase extends ListOperationBase {

  /**
   * Removes an item from a list as configured.
   *
   * @return mixed
   *   The removed item. May be NULL if no item was removed.
   */
  protected function removeItem() {
    if (!($list = $this->getItemList())) {
      return NULL;
    }

    $item = NULL;

    switch ($this->configuration['method']) {

      case 'first':
        if ($list instanceof DataTransferObject) {
          $item = $list->shift();
        }
        elseif ($values = $list->getValue()) {
          $item = array_shift($values);
          $list->setValue(array_values($values));
        }
        break;

      case 'last':
        if ($list instanceof DataTransferObject) {
          $item = $list->pop();
        }
        elseif ($values = $list->getValue()) {
          $item = array_pop($values);
          $list->setValue($values);
        }
        break;

      case 'index':
        $index = trim((string) $this->tokenServices->replaceClear($this->configuration['index']));
        if (!$index || ctype_digit($index) || !($list instanceof ComplexDataInterface)) {
          $index = (int) $index;
        }
        if ($list instanceof DataTransferObject) {
          $item = $list->removeByName($index);
        }
        elseif ($values = $list->getValue()) {
          $item = $values[$index] ?? NULL;
          unset($values[$index]);
          $list->setValue(array_values($values));
        }
        break;

      case 'value':
        $value = $this->getValueToRemove();
        if ($list instanceof DataTransferObject) {
          $item = $list->remove($value);
        }
        elseif ($values = $list->getValue()) {
          if ($value instanceof TypedDataInterface) {
            $value = $value->getValue();
          }
          $index = array_search($value, $values, TRUE);
          if ($index !== FALSE) {
            $item = $values[$index];
            unset($values[$index]);
          }
          $list->setValue(array_values($values));
        }
        break;

    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'method' => 'first',
      'index' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#default_value' => $this->configuration['method'],
      '#weight' => 0,
      '#options' => [
        'first' => $this->t('Drop first'),
        'last' => $this->t('Drop last'),
        'index' => $this->t('Drop by specified index key'),
        'value' => $this->t('Drop by specified value'),
      ],
      '#required' => TRUE,
    ];
    $form['index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index key'),
      '#description' => $this->t('When using the method <em>Drop by specified index key</em>, then an index key must be specified here. This field supports tokens.'),
      '#default_value' => $this->configuration['index'],
      '#weight' => 10,
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->getValue('method') === 'index' && (trim((string) $form_state->getValue('index', '')) === '')) {
      $form_state->setError($form['index'], $this->t('You must specify an index when using the "index" method.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['method'] = $form_state->getValue('method');
    $this->configuration['index'] = $form_state->getValue('index');
  }

  /**
   * Get the value to remove, when using the "value" removal method.
   *
   * @return mixed
   *   The value to remove.
   */
  abstract protected function getValueToRemove();

}
