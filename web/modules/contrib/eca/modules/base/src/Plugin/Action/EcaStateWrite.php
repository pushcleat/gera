<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Service\YamlParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Action to store arbitrary value to ECA's key value store.
 *
 * @Action(
 *   id = "eca_state_write",
 *   label = @Translation("Persistent state: write"),
 *   description = @Translation("Writes a value into the Drupal state system by the given key.")
 * )
 */
class EcaStateWrite extends ConfigurableActionBase {

  /**
   * The YAML parser.
   *
   * @var \Drupal\eca\Service\YamlParser
   */
  protected YamlParser $yamlParser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_base\Plugin\Action\EcaStateWrite $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setYamlParser($container->get('eca.service.yaml_parser'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $value = $this->configuration['value'];

    if ($this->configuration['use_yaml']) {
      try {
        $value = $this->yamlParser->parse($value);
      }
      catch (ParseException $e) {
        \Drupal::logger('eca')->error('Tried parsing a state value item in action "eca_state_write" as YAML format, but parsing failed.');
        return;
      }
    }
    else {
      $value = $this->tokenServices->getOrReplace($value);
    }

    $this->state->set($this->configuration['key'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'key' => '',
      'value' => '',
      'use_yaml' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State key'),
      '#default_value' => $this->configuration['key'],
      '#weight' => -30,
      '#description' => $this->t('The key of the Drupal state.'),
    ];
    $form['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('The value of the state'),
      '#default_value' => $this->configuration['value'],
      '#weight' => -20,
      '#description' => $this->t('The key, where the value is stored into.'),
    ];
    $form['use_yaml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interpret above config value as YAML format'),
      '#description' => $this->t('Nested data can be set using YAML format, for example <em>mykey: myvalue</em>. When using this format, this option needs to be enabled. When using tokens and YAML altogether, make sure that tokens are wrapped as a string. Example: <em>title: "[node:title]"</em>'),
      '#default_value' => $this->configuration['use_yaml'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['key'] = $form_state->getValue('key');
    $this->configuration['value'] = $form_state->getValue('value');
    $this->configuration['use_yaml'] = !empty($form_state->getValue('use_yaml'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Set the YAML parser.
   *
   * @param \Drupal\eca\Service\YamlParser $yaml_parser
   *   The YAML parser.
   */
  public function setYamlParser(YamlParser $yaml_parser): void {
    $this->yamlParser = $yaml_parser;
  }

}
