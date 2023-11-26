<?php

namespace Drupal\field_group_layout\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'default' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "layouts",
 *   label = @Translation("Layouts"),
 *   description = @Translation("Add a layout formatter"),
 *   supported_contexts = {
 *     "form",
 *     "view"
 *   }
 * )
 */
class LayoutFormatter extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    // Keep using preRender parent for BC.
    parent::preRender($element, $processed_object);
    if (isset($this->group->field_layout)) {
      $element['#field_layout'] = $this->group->field_layout ?: NULL;
    }

    $element += [
      '#type' => 'field_group_layouts',
      '#effect' => $this->getSetting('effect'),
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getUniqueId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += ['#attributes' => ['class' => $classes]];
    }
    $element['#attached']['library'][] = 'field_group/layouts';
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    if (isset($this->group->field_layout)) {
      $element['#field_layout'] = $this->group->field_layout ?: NULL;
    }
    parent::preRender($element, $rendering_object);
    $this->process($element, $rendering_object);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();
    $layoutmanager = \Drupal::service('plugin.manager.core.layout');
    $group = $this->group;
    $display = $this->getDisplaySettings();
    if ($display) {
      $parent_group = $display->getThirdPartySettings('field_group')[$group->group_name];
    }

    isset($this->group->group_name) ? $form['field_layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a layout'),
      '#description' => $this->t('<b>Changing the Layout will move the fields to the new region provided by the layout.</b>'),
      '#options' => $layoutmanager->getLayoutOptions(),
      '#default_value' => !empty($parent_group) ? $parent_group['field_layout'] : '_none',
      '#states' => [
        'visible' => [
          ':input[name="group_formatter"]' => ['value' => 'layouts'],
        ],
      ],
    ] : NULL;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplaySettings() {
    $group = $this->group;
    if ($this->context === 'form') {
      $display = EntityFormDisplay::load("{$group->entity_type}.{$group->bundle}.{$group->mode}");
    }
    elseif ($this->context === 'view') {
      $display = EntityViewDisplay::load("{$group->entity_type}.{$group->bundle}.{$group->mode}");
    }
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    \Drupal::cache()->invalidate('field_groups');
    \Drupal::cache()->invalidate('rendered');
    $summary = [];
    $group = $this->group;
    if (isset($group) && $this->getSetting('field_layout')) {
      if ($group->field_layout === $this->getSetting('field_layout')) {
        return $summary;
      }

      $get_regions = _field_group_layout_get_region_by_layout_id($this->getSetting('field_layout'));
      if (!empty($group->region_mapping) && !empty($get_regions)) {
        if ($this->context == 'form') {
          $display = EntityFormDisplay::load("{$group->entity_type}.{$group->bundle}.{$group->mode}");
        }
        elseif ($this->context == 'view') {
          $display = EntityViewDisplay::load("{$group->entity_type}.{$group->bundle}.{$group->mode}");
        }
        if (!empty($display->getThirdPartySettings('field_group')[$group->group_name])) {

          $display_settings = $display->getThirdPartySettings('field_group');
          $display_settings = $this->updateGroup($group, $display->getThirdPartySettings('field_group'), $get_regions, $display);
          $parent_group = $display->getThirdPartySettings('field_group')[$group->group_name];
          foreach ($get_regions as $new_region_id => $new_region_name) {
            if (!array_key_exists($group->group_name . '_' . $new_region_id, $display_settings)) {
              $formatters = \Drupal::service('plugin.manager.field_group.formatters');
              $new_group1 = (object) [
                'group_name' => $group->group_name . '_' . $new_region_id,
                'entity_type' => $group->entity_type,
                'bundle' => $group->bundle,
                'mode' => $group->mode,
                'context' => $this->context,
                'children' => [],
                'parent_name' => $group->group_name,
                'weight' => 20,
                'format_type' => 'default',
                'region' => 'content',
              ];
              $new_group1->format_settings = $group->format_settings;
              $new_group1->label = $new_region_name;
              unset($new_group1->format_settings['label']);
              $new_group1->format_settings += $formatters->getDefaultSettings('layouts', $this->context);
              field_group_group_save($new_group1);
            }
            if ($new_group1) {
              if ($this->context == 'form') {
                $display = EntityFormDisplay::load("{$group->entity_type}.{$group->bundle}.{$group->mode}");
              }
              elseif ($this->context == 'view') {
                $display = EntityViewDisplay::load("{$group->entity_type}.{$group->bundle}.{$group->mode}");
              }
              if (!array_key_exists($group->group_name . '_' . $new_region_id, $parent_group['children'])) {
                $parent_group['children'][] = $new_group1->group_name;
              }
            }
          }
          $parent_group['field_layout'] = $this->getSetting('field_layout');
          $parent_group['format_type'] = 'layouts';
          $display->setThirdPartySetting('field_group', $group->group_name, $parent_group)->save();
          \Drupal::cache()->invalidate('field_groups');
          \Drupal::cache()->invalidate('rendered');
          Cache::invalidateTags($this->getDisplaySettings()->getCacheTags());
        }
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function updateGroup($group, &$display_settings, $new_regions, &$display) {
    if (!isset($group)) {
      return [];
    }
    $parent_group = $display_settings[$group->group_name];
    if (empty($new_regions)) {
      return [];
    }
    foreach ($parent_group['children'] as $key => $value) {
      $key_to_find = explode($group->group_name . '_', $value);

      if (!in_array($key_to_find, $new_regions)) {
        $key_to_remove = array_search($value, $parent_group['children']);
        unset($parent_group['children'][$key_to_remove]);
        $display->setThirdPartySetting('field_group', $group->group_name, $parent_group);
        $display->unsetThirdPartySetting('field_group', $value);
      }
    }
    $display->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return parent::defaultSettings($context);
  }

}
