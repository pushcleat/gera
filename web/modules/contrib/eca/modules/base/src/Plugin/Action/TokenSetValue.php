<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Service\YamlParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Action to set an arbitrary token value.
 *
 * @Action(
 *   id = "eca_token_set_value",
 *   label = @Translation("Token: set value"),
 *   description = @Translation("Define a locally available token by a specific name and value.")
 * )
 */
class TokenSetValue extends ConfigurableActionBase {

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
    /** @var \Drupal\eca_base\Plugin\Action\TokenSetValue $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setYamlParser($container->get('eca.service.yaml_parser'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $name = $this->configuration['token_name'];
    $value = $this->configuration['token_value'];

    if ($this->configuration['use_yaml']) {
      try {
        $value = $this->yamlParser->parse($value);
      }
      catch (ParseException $e) {
        \Drupal::logger('eca')->error('Tried parsing a Token value in action "eca_token_set_value" as YAML format, but parsing failed.');
        return;
      }
    }
    else {
      // Allow direct assignment of available data from the Token environment.
      $value = $this->tokenServices->getOrReplace($value);
    }

    $this->setToken($name, $value);
  }

  /**
   * Sets the token.
   *
   * @param string $name
   *   The token name.
   * @param mixed $value
   *   The token value.
   */
  protected function setToken(string $name, $value): void {
    $this->tokenServices->addTokenData($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => '',
      'token_value' => '',
      'use_yaml' => FALSE,
    ] + parent::defaultConfiguration();
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
      '#weight' => -30,
      '#description' => $this->t('Provide the name of a token where the value should be stored.'),
      '#eca_token_reference' => TRUE,
    ];
    $form['token_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value of the token'),
      '#default_value' => $this->configuration['token_value'],
      '#weight' => -20,
      '#description' => $this->t('The value of the token. Other tokens can be reused here, for example <em>[node]</em> will directly assign the node entity, if available.'),
    ];
    $form['use_yaml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interpret above value as YAML format'),
      '#description' => $this->t('Nested data can be set using YAML format, for example <em>mykey: "My value"</em>. When using this format, this option needs to be enabled. When using tokens and YAML altogether, make sure that tokens are wrapped as a string. Example: <em>title: "[node:title]"</em>'),
      '#default_value' => $this->configuration['use_yaml'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['token_value'] = $form_state->getValue('token_value');
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
