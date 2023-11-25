<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Build formatted text.
 *
 * @Action(
 *   id = "eca_render_text",
 *   label = @Translation("Render: text"),
 *   description = @Translation("Build a renderable text element."),
 *   deriver = "Drupal\eca_render\Plugin\Action\TextDeriver"
 * )
 */
class Text extends RenderElementActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'text' => '',
      'format' => 'plain_text',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $this->configuration['text'],
      '#required' => TRUE,
      '#weight' => 100,
    ];
    $format_storage = $this->entityTypeManager->getStorage('filter_format');
    $format_options = [];
    foreach ($format_storage->loadMultiple() as $format) {
      $format_options[$format->id()] = $format->label();
    }
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter format'),
      '#options' => $format_options,
      '#default_value' => $this->configuration['format'],
      '#required' => TRUE,
      '#weight' => 110,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['text'] = $form_state->getValue('text', '');
    $this->configuration['format'] = $form_state->getValue('format');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuild(array &$build): void {
    $text = $this->tokenServices->replaceClear($this->configuration['text']);
    $format = $this->configuration['format'] ?? '';
    if ($format === '') {
      $build = ['#markup' => $text];
    }
    else {
      $build = [
        '#type' => 'processed_text',
        '#text' => $text,
        '#format' => $format,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = 'filter';
    if ((($this->configuration['format'] ?? '') !== '') && $filter_format = $this->entityTypeManager->getStorage('filter_format')->load($this->configuration['format'])) {
      $dependencies[$filter_format->getConfigDependencyKey()][] = $filter_format->getConfigDependencyName();
    }
    return $dependencies;
  }

}
