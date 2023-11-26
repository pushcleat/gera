<?php

namespace Drupal\field_group_layout\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a field group layout.
 *
 * @FormElement("field_group_layouts")
 */
class Layouts extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processLayouts'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['field_group_layouts'],
    ];
  }

  /**
   * Process the layout item.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The processed element.
   */
  public static function processLayouts(array &$element, FormStateInterface $form_state) {
    $element['#attached']['library'][] = 'field_group_layout/layouts';
    $element['#attached']['library'][] = 'field_group/core';

    // Add the effect class.
    if (isset($element['#effect'])) {
      if (!isset($element['#attributes']['class'])) {
        $element['#attributes']['class'] = [];
      }
      $element['#attributes']['class'][] = 'effect-' . $element['#effect'];
    }

    return $element;
  }

}
