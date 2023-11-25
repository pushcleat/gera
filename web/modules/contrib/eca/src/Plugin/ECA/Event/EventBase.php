<?php

namespace Drupal\eca\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\ECA\EcaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ECA event plugins.
 */
abstract class EventBase extends EcaPluginBase implements EventInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaPluginBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConfiguration($configuration);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  final public function eventClass(): string {
    return $this->pluginDefinition['event_class'];
  }

  /**
   * {@inheritdoc}
   */
  final public function eventName(): string {
    return $this->pluginDefinition['event_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    // By default return a small wildcard that should match up for every event
    // that is of the class as returned by ::drupalEventClass.
    return '*';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): EventBase {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {}

}
