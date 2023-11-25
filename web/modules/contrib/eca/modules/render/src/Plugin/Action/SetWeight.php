<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\RenderEventInterface;

/**
 * Set the weight of an existing render array element.
 *
 * @Action(
 *   id = "eca_render_set_weight",
 *   label = @Translation("Render: set weight"),
 *   description = @Translation("Set the weight of an existing render array element. Only works when reacting upon a rendering event, such as <em>Build form</em> or <em>Build ECA Block</em>.")
 * )
 */
class SetWeight extends RenderActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'name' => '',
      'weight' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'alwaysFalse'],
      ],
      '#title' => $this->t('Machine name'),
      '#description' => $this->t('Specify the machine name / key of the render element.'),
      '#default_value' => $this->configuration['name'],
      '#weight' => -30,
      '#required' => TRUE,
    ];
    $form['weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element weight'),
      '#description' => $this->t('The weight as integer number. This field supports tokens.'),
      '#default_value' => $this->configuration['weight'],
      '#weight' => -25,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['name'] = $form_state->getValue('name');
    $this->configuration['weight'] = $form_state->getValue('weight');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $event = $this->event;
    if (!($event instanceof RenderEventInterface)) {
      return;
    }

    $name = trim((string) $this->tokenServices->replaceClear($this->configuration['name']));
    $weight = trim((string) $this->tokenServices->replaceClear($this->configuration['weight']));
    $build = &$event->getRenderArray();
    $element = &$this->getTargetElement($name, $build);

    if ($element) {
      if ($weight === '') {
        unset($element['#weight']);
      }
      elseif (is_numeric($weight)) {
        $element['#weight'] = (int) $weight;
      }
    }
  }

}
