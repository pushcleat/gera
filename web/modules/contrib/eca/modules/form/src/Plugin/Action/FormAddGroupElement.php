<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Plugin\FormFieldPluginTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add a grouping element to a form.
 *
 * @Action(
 *   id = "eca_form_add_group_element",
 *   label = @Translation("Form: add grouping element"),
 *   description = @Translation("Add a collapsible details element (also known as fieldset) for grouping form fields."),
 *   type = "form"
 * )
 */
class FormAddGroupElement extends FormActionBase {

  use FormFieldPluginTrait;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    // Reverse the order of lookups.
    $instance->lookupKeys = ['array_parents', 'parents'];
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (!($form = &$this->getCurrentForm())) {
      return;
    }
    $name = trim((string) $this->tokenServices->replace($this->configuration['name']));
    if ($name === '') {
      throw new \InvalidArgumentException('Cannot use an empty string as element name');
    }
    $this->configuration['field_name'] = $name;
    $name = $this->getFieldNameAsArray();

    $group_element = [
      '#type' => 'details',
      '#value' => $this->tokenServices->replaceClear($this->configuration['summary_value']),
      '#title' => $this->tokenServices->replaceClear($this->configuration['title']),
      '#weight' => (int) $this->configuration['weight'],
      '#open' => $this->configuration['open'],
    ];

    if ($this->configuration['introduction_text'] !== '') {
      $introduction_text = (string) $this->tokenServices->replaceClear($this->configuration['introduction_text']);
      if ($introduction_text !== '') {
        $group_element['introduction_text'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="introduction-text">',
          '#markup' => $introduction_text,
          '#suffix' => '</div>',
          '#weight' => -1000,
        ];
      }
    }
    if ($this->configuration['summary_value'] !== '') {
      $summary_value = (string) $this->tokenServices->replaceClear($this->configuration['summary_value']);
      if ($summary_value !== '') {
        $group_element['#value'] = $summary_value;
      }
    }

    $this->insertFormElement($form, $name, $group_element);

    if ($fields = (string) $this->tokenServices->replace($this->configuration['fields'])) {
      $name_string = implode('][', $name);
      foreach (DataTransferObject::buildArrayFromUserInput($fields) as $field) {
        $this->configuration['field_name'] = $field;
        if ($field_element = &$this->getTargetElement()) {
          $field_element['#group'] = $name_string;

          // @todo Remove this workaround once #2190333 got fixed.
          if (empty($field_element['#process']) && empty($field_element['#pre_render']) && isset($field_element['#type'])) {
            $type = $field_element['#type'];
            /** @var \Drupal\Core\Render\ElementInfoManager $element_info */
            $element_info = \Drupal::service('plugin.manager.element_info');
            if ($element_info->hasDefinition($type)) {
              $field_element += $element_info->getInfo($type);
            }
          }
          $needs_process_callbacks = TRUE;
          if (!empty($field_element['#process'])) {
            foreach ($field_element['#process'] as $process_callback) {
              if (is_array($process_callback) && end($process_callback) === 'processGroup') {
                $needs_process_callbacks = FALSE;
                break;
              }
            }
          }
          if ($needs_process_callbacks) {
            $field_element['#pre_render'][] = [
              RenderElement::class,
              'preRenderGroup',
            ];
            $field_element['#process'][] = [
              RenderElement::class,
              'processGroup',
            ];
          }

        }
        unset($field_element);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'name' => '',
      'title' => '',
      'open' => FALSE,
      'weight' => '0',
      'fields' => '',
      'introduction_text' => '',
      'summary_value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element name'),
      '#description' => $this->t('The element name is a machine name and is used for being identified when rendering the form. Example: <em>name_info</em>'),
      '#weight' => -10,
      '#default_value' => $this->configuration['name'],
      '#required' => TRUE,
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('This will be shown to the user in the form as grouping title.'),
      '#weight' => -9,
      '#default_value' => $this->configuration['title'],
      '#required' => TRUE,
    ];
    $form['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#default_value' => $this->configuration['open'],
      '#description' => $this->t('Whether the group should be open to edit or collapsed when displayed.'),
      '#weight' => -8,
    ];
    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Element weight'),
      '#description' => $this->t('The lower the weight, the element appears before other elements having a higher weight.'),
      '#default_value' => $this->configuration['weight'],
      '#weight' => -7,
      '#required' => TRUE,
    ];
    $form['fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fields'),
      '#description' => $this->t('Machine names of form fields that should be grouped together. Define multiple values separated with comma. Example: <em>first_name,last_name</em>'),
      '#weight' => -6,
      '#default_value' => $this->configuration['fields'],
    ];
    $form['introduction_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Introduction text'),
      '#description' => $this->t('Here you can set an introduction text of the group.'),
      '#weight' => -5,
      '#default_value' => $this->configuration['introduction_text'],
    ];
    $form['summary_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary value'),
      '#description' => $this->t('Here you can set the summary text of the group.'),
      '#weight' => -4,
      '#default_value' => $this->configuration['summary_value'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['name'] = $form_state->getValue('name');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['open'] = !empty($form_state->getValue('open'));
    $this->configuration['weight'] = $form_state->getValue('weight');
    $this->configuration['fields'] = $form_state->getValue('fields');
    $this->configuration['introduction_text'] = $form_state->getValue('introduction_text');
    $this->configuration['summary_value'] = $form_state->getValue('summary_value');
  }

}
