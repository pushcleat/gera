<?php

/**
 * @file
 * Preprocessors for fieldgroup elements.
 */

/**
 * Prepares variables for fieldgroup with onecol design.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group__layout_onecol(array &$variables) {

  $element = $variables['content'];

  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }

  if (!empty($element)) {
    $regions = _field_group_layout_get_region_by_layout_id($element['#field_layout']);
    foreach ($element as $key => $value) {
      if (strpos($key, $element['#group_name']) !== FALSE) {
        if (!empty($regions)) {
          foreach ($regions as $r_key => $r_value) {
            if (strpos($key, $r_key) !== FALSE) {
              $variables['content']['group'][$r_key] = $element[$key];
            }
          }
        }
      }
    }
    if (!empty($element['#groups'])) {
      foreach ($element as $key_form => $value_form) {
        foreach ($regions as $r_key_form => $r_value_form) {
          if (is_array($value_form) && array_key_exists('#title', $value_form)) {
            if ($value_form['#title'] === $r_value_form) {
              $variables['content']['group'][$r_key_form] = $element[$key_form];
            }
          }
        }
      }
    }
  }

  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = ['class' => 'layouts_layout_layout_onecol'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}

/**
 * Prepares variables for fieldgroup with twocol design.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group__layout_simple_twocol(array &$variables) {
  $element = $variables['content'];
  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }

  if (!empty($element)) {
    $regions = _field_group_layout_get_region_by_layout_id($element['#field_layout']);
    foreach ($element as $key => $value) {
      if (strpos($key, $element['#group_name']) !== FALSE) {
        if (!empty($regions)) {
          foreach ($regions as $r_key => $r_value) {
            if (strpos($key, $r_key) !== FALSE) {
              $variables['content']['group'][$r_key] = $element[$key];
            }
          }
        }
      }
    }
    if (!empty($element['#groups'])) {
      foreach ($element as $key_form => $value_form) {
        foreach ($regions as $r_key_form => $r_value_form) {
          if (is_array($value_form) && array_key_exists('#title', $value_form)) {
            if ($value_form['#title'] === $r_value_form) {
              $variables['content']['group'][$r_key_form] = $element[$key_form];
            }
          }
        }
      }
    }
  }

  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = ['class' => 'layouts_layout_twocol'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for fieldgroup with twocol design.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group__layout_twocol(array &$variables) {
  $element = $variables['content'];
  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }

  if (!empty($element)) {
    $regions = _field_group_layout_get_region_by_layout_id($element['#field_layout']);
    foreach ($element as $key => $value) {
      if (strpos($key, $element['#group_name']) !== FALSE) {
        if (!empty($regions)) {
          foreach ($regions as $r_key => $r_value) {
            if (strpos($key, $r_key) !== FALSE) {
              $variables['content']['group'][$r_key] = $element[$key];
            }
          }
        }
      }
    }
    if (!empty($element['#groups'])) {
      foreach ($element as $key_form => $value_form) {
        foreach ($regions as $r_key_form => $r_value_form) {
          if (is_array($value_form) && array_key_exists('#title', $value_form)) {
            if ($value_form['#title'] === $r_value_form) {
              $variables['content']['group'][$r_key_form] = $element[$key_form];
            }
          }
        }
      }
    }
  }

  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = ['class' => 'layouts_layout_twocol'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for fieldgroup with twocol_bricks design.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group__layout_twocol_bricks(array &$variables) {

  $element = $variables['content'];

  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }
  if (!empty($element)) {
    $regions = _field_group_layout_get_region_by_layout_id($element['#field_layout']);
    foreach ($element as $key => $value) {
      if (strpos($key, $element['#group_name']) !== FALSE) {
        if (!empty($regions)) {
          foreach ($regions as $r_key => $r_value) {
            if (strpos($key, $r_key) !== FALSE) {
              $variables['content']['group'][$r_key] = $element[$key];
            }
          }
        }
      }
    }

    if (!empty($element['#groups'])) {
      foreach ($element as $key_form => $value_form) {
        foreach ($regions as $r_key_form => $r_value_form) {
          if (is_array($value_form) && array_key_exists('#title', $value_form)) {
            if ($value_form['#title'] === $r_value_form) {
              $variables['content']['group'][$r_key_form] = $element[$key_form];
            }
          }
        }
      }
    }
  }
  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = ['class' => 'layouts_layout_twocol_bricks'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}

/**
 * Prepares variables for fieldgroup with 3-col-25-50-25 design.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group__layout_threecol_25_50_25(array &$variables) {

  $element = $variables['content'];

  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }

  if (!empty($element)) {
    $regions = _field_group_layout_get_region_by_layout_id($element['#field_layout']);
    foreach ($element as $key => $value) {
      if (strpos($key, $element['#group_name']) !== FALSE) {
        if (!empty($regions)) {
          foreach ($regions as $r_key => $r_value) {
            if (strpos($key, $r_key) !== FALSE) {
              $variables['content']['group'][$r_key] = $element[$key];
            }
          }
        }
      }
    }
    if (!empty($element['#groups'])) {
      foreach ($element as $key_form => $value_form) {
        foreach ($regions as $r_key_form => $r_value_form) {
          if (is_array($value_form) && array_key_exists('#title', $value_form)) {
            if ($value_form['#title'] === $r_value_form) {
              $variables['content']['group'][$r_key_form] = $element[$key_form];
            }
          }
        }
      }
    }
  }
  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = ['class' => 'layouts_threecol_25_50_25'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}

/**
 * Prepares variables for fieldgroup with 3-col-33-34-33 design.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group__layout_threecol_33_34_33(array &$variables) {

  $element = $variables['content'];

  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }

  if (!empty($element)) {
    $regions = _field_group_layout_get_region_by_layout_id($element['#field_layout']);
    foreach ($element as $key => $value) {
      if (strpos($key, $element['#group_name']) !== FALSE) {
        if (!empty($regions)) {
          foreach ($regions as $r_key => $r_value) {
            if (strpos($key, $r_key) !== FALSE) {
              $variables['content']['group'][$r_key] = $element[$key];
            }
          }
        }
      }
    }
    if (!empty($element['#groups'])) {
      foreach ($element as $key_form => $value_form) {
        foreach ($regions as $r_key_form => $r_value_form) {
          if (is_array($value_form) && array_key_exists('#title', $value_form)) {
            if ($value_form['#title'] === $r_value_form) {
              $variables['content']['group'][$r_key_form] = $element[$key_form];
            }
          }
        }
      }
    }
  }

  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = ['class' => 'layouts_threecol_33_34_33'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}
