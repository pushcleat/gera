<?php

namespace Drupal\field_group_layout\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\field_group\FieldgroupUi;
use Drupal\field_group\Form\FieldGroupAddForm;
use Drupal\Core\Layout\LayoutPluginManagerInterface;

/**
 * Provides an overridden version of the field group "add" form.
 */
class FieldGroupAddFormOverride extends FieldGroupAddForm {

  /**
   * Build the formatter selection step.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildFormatterSelectionForm(array &$form, FormStateInterface $form_state) {
    parent::buildFormatterSelectionForm($form, $form_state);
    /** @var LayoutPluginManagerInterface $layoutPluginManager */
    $layoutPluginManager = \Drupal::service('plugin.manager.core.layout');
    $form['add']['group_formatter']['#ajax'] = [
      'callback' => '::getLayoutRegions',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Updating...'),
      ],
      'event' => 'change',
      'wrapper' => 'update-group-wrapper',
    ];

    $form['new_group_wrapper']['field_layout'] = [
      '#type' => 'select',
      '#empty_option' => $this->t('- Select a layout -'),
      '#options' => array_filter($layoutPluginManager->getLayoutOptions()),
      '#default_value' => '_none',
      '#states' => [
        'visible' => [
          ':input[name="group_formatter"]' => ['value' => 'layouts'],
        ],
      ],
      '#ajax' => [
        'callback' => '::getLayoutRegions',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating...'),
        ],
        'event' => 'change',
        'wrapper' => 'update-group-wrapper',
      ],
    ];

    $form['new_group_wrapper']['icon_wrapper'] = [
      '#type' => 'container',
      '#id' => 'field-layout-icon-wrapper',
      '#tree' => TRUE,
    ];

    $element_triggered = $form_state->getTriggeringElement();
    $layout_selected = FALSE;
    if (!empty($element_triggered) && $element_triggered['#name'] === 'field_layout') {
      $layout_selected = $form_state->getValue('field_layout');
    }

    $layout_options = [];
    if ($layout_selected && $layoutPluginManager instanceof LayoutPluginManagerInterface) {
      $layout_definition = $layoutPluginManager->getDefinition($layout_selected);
      foreach ($layout_definition->getRegions() as $key => $value) {
        $layout_options[$key] = $value['label']->__toString();
      }
    }

    $form['table_definition'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-group-wrapper'],
    ];

    if ($layout_selected) {
      $layout_definition = $layoutPluginManager->getDefinition($layout_selected);
      if ($layout_definition instanceof LayoutDefinition) {
        $form['table_definition']['icon'] = $layout_definition->getIcon();
      }
    }

    if ($form_state->getValue('group_formatter', NULL) === 'layouts') {
      $form['table_definition']['region_table'] = [
        '#type' => 'table',
        '#caption' => $this->t('<b>Please select unique fields in each group below or move the fields from display form once the layout is generated,<br> To define more layout check this example in: <i>core/modules/layout_discovery/layout_discovery.layouts.yml</i></b>'),
        '#header' => [
          'field_group_region_name' => 'Region Name',
          'field_group_fields' => 'Select a Field',
        ],
        '#empty' => $this->t('There are no items yet.', []),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'table-sort-weight',
          ],
        ],
        '#states' => [
          '!visible' => [
            ':input[name="group_formatter"]' => ['value' => ''],
          ],
        ],
      ];

      $entityFieldManager = \Drupal::service('entity_field.manager');
      $fields = $entityFieldManager->getFieldDefinitions($this->entityTypeId, $this->bundle);
      $display_fields = [];
      foreach ($fields as $key => $value) {
        $display_fields[$key] = $key;
      }
      foreach ($layout_options as $region_mn => $region_label) {
        $form['table_definition']['region_table'][$region_mn]['field_group_region_name'] = [
          '#type' => 'textfield',
          '#maxlength' => 255,
          '#default_value' => $region_label,
          '#disabled' => TRUE,
        ];

        $form['table_definition']['region_table'][$region_mn]['field_group_fields'] = [
          '#type' => 'select',
          '#title' => $this->t('Select Fields for the field group layout.'),
          '#options' => $display_fields ?: [],
          '#multiple' => TRUE,
        ];
      }
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function getLayoutRegions(array &$form, FormStateInterface $form_state) {
    return $form['table_definition'];
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function processGroupName(array &$form, FormStateInterface $form_state) {
    return $form['new_group_wrapper']['group_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') === 'formatter') {
      $form_state->set('step', 'configuration');
      $form_state->set('group_label', $form_state->getValue('label'));
      $form_state->set('group_name', $form_state->getValue('group_name'));
      $form_state->set('group_formatter', $form_state->getValue('group_formatter'));
      if ($form_state->getValue('group_formatter') === 'layouts') {
        $form_state->set('field_layout', $form_state->getValue('field_layout'));
        $form_state->set('region_mapping', $form_state->getValue('region_table'));
      }
      $form_state->setRebuild();
    }
    else {
      $all_children = [];
      if (($all_subgroup = $form_state->get('region_mapping')) && $form_state->get('group_formatter') === 'layouts') {
        foreach ($all_subgroup as $group_key => $group_value) {
          $all_children_sub = [];
          if (!empty($group_value['field_group_fields'])) {
            foreach ($group_value['field_group_fields'] as $key_map => $value_map) {
              $all_children_sub[] = $value_map;
            }
          }
          $new_group1 = (object) [
            'group_name' => $form_state->get('group_name') . '_' . $group_key,
            'entity_type' => $this->entityTypeId,
            'bundle' => $this->bundle,
            'mode' => $this->mode,
            'context' => $this->context,
            'children' => $all_children_sub,
            'parent_name' => $form_state->get('group_name'),
            'weight' => 20,
            'format_type' => 'default',
            'region' => 'content',
          ];
          $new_group1->format_settings = $form_state->getValue('format_settings');
          $new_group1->label = $group_value['field_group_region_name'];
          unset($new_group1->format_settings['label']);
          $new_group1->format_settings += $this->fieldGroupFormatterPluginManager->getDefaultSettings($form_state->get('group_formatter'), $this->context);
          field_group_group_save($new_group1);
          if ($this->context === 'form') {
            $display = EntityFormDisplay::load("{$this->entityTypeId}.{$this->bundle}.{$this->mode}");
          }
          elseif ($this->context === 'view') {
            $display = EntityViewDisplay::load("{$this->entityTypeId}.{$this->bundle}.{$this->mode}");
          }
          $display->save();
          $all_children[] = $form_state->get('group_name') . '_' . $group_key;
        }
      }
      $new_group = (object) [
        'group_name' => $form_state->get('group_name'),
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'mode' => $this->mode,
        'context' => $this->context,
        'children' => $all_children,
        'parent_name' => '',
        'weight' => 20,
        'format_type' => $form_state->get('group_formatter'),
        'region' => 'content',
        'field_layout' => ($form_state->get('field_layout') && $form_state->get('group_formatter') === 'layouts') ? $form_state->get('field_layout') : NULL,
        'region_mapping' => ($form_state->get('region_mapping') && $form_state->get('group_formatter') === 'layouts') ? $form_state->get('region_mapping') : NULL,
      ];

      $new_group->format_settings = $form_state->getValue('format_settings');
      $new_group->label = $new_group->format_settings['label'];
      unset($new_group->format_settings['label']);
      $new_group->format_settings += $this->fieldGroupFormatterPluginManager->getDefaultSettings($form_state->get('group_formatter'), $this->context);

      field_group_group_save($new_group);

      // Store new group information for any additional submit handlers.
      $groups_added = $form_state->get('groups_added');
      $groups_added['_add_new_group'] = $new_group->group_name;
      $this->messenger->addMessage($this->t('New group %label successfully created.', ['%label' => $new_group->label]));

      $form_state->setRedirectUrl(FieldgroupUi::getFieldUiRoute($new_group));
      \Drupal::cache()->invalidate('field_groups');
    }
  }

}
