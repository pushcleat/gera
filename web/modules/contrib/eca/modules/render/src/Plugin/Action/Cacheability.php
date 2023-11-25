<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\RenderEventInterface;

/**
 * Add cacheability metadata to a render array.
 *
 * @Action(
 *   id = "eca_render_cacheability",
 *   label = @Translation("Render: cacheability"),
 *   description = @Translation("Add cacheability metadata to a render array. Only works when reacting upon a rendering event, such as ""Build form"" or ""Build ECA Block"".")
 * )
 */
class Cacheability extends RenderActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'cache_type' => 'tags',
      'cache_value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['cache_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'tags' => $this->t('Cache tags'),
        'contexts' => $this->t('Cache contexts'),
        'max-age' => $this->t('Max age'),
      ],
      '#description' => $this->t('<a href=":url" target="_blank" rel="nofollow noreferrer">Click here</a> to get more info about cacheability metadata.', [
        ':url' => 'https://www.drupal.org/docs/8/api/cache-api/cache-api#s-cacheability-metadata',
      ]),
      '#default_value' => $this->configuration['cache_type'],
      '#required' => TRUE,
      '#weight' => 10,
    ];
    $form['cache_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#description' => $this->t('This field supports tokens. Separate multiple values with comma. When using <em>Max age</em>, the value must be a valid number (integer).'),
      '#default_value' => $this->configuration['cache_value'],
      '#required' => FALSE,
      '#weight' => 20,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['cache_type'] = $form_state->getValue('cache_type');
    $this->configuration['cache_value'] = $form_state->getValue('cache_value');
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

    $build = &$event->getRenderArray();
    $key = $this->configuration['cache_type'];

    if (!isset($build['#cache'][$key])) {
      $build['#cache'][$key] = [];
    }
    $value = trim((string) $this->tokenServices->replaceClear($this->configuration['cache_value']));
    if ($value !== '') {
      if ($key === 'max-age') {
        $max_age = is_numeric($value) ? (int) $value : 0;
        $build['#cache']['max-age'] = isset($build['#cache']['max-age']) ? Cache::mergeMaxAges($build['#cache']['max-age'], $max_age) : $max_age;
      }
      else {
        foreach (explode(',', $value) as $v) {
          $v = trim($v);
          if (($v !== '') && !in_array($v, $build['#cache'][$key], TRUE)) {
            $build['#cache'][$key][] = $v;
          }
        }
      }
    }
  }

}
