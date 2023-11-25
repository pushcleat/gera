<?php

namespace Drupal\eca_cache\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Action to read from cache.
 *
 * @Action(
 *   id = "eca_cache_read",
 *   label = @Translation("Cache: read"),
 *   description = @Translation("Read a value item from cache and store it as a token.")
 * )
 */
class CacheRead extends CacheActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => '',
    ]
    + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#description' => $this->t('The cache item value will be loaded into this specified token.'),
      '#required' => TRUE,
      '#weight' => -30,
      '#eca_token_reference' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!($cache = $this->getCacheBackend()) || !($key = $this->getCacheKey()) || !($name = trim($this->configuration['token_name']))) {
      return;
    }

    if ($cached = $cache->get($key)) {
      $this->tokenServices->addTokenData($name, $cached->data);
    }
  }

}
