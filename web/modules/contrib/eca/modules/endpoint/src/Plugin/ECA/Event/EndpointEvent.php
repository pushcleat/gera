<?php

namespace Drupal\eca_endpoint\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\EcaPluginBase;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_endpoint\EndpointEvents;
use Drupal\eca_endpoint\Event\EndpointAccessEvent;
use Drupal\eca_endpoint\Event\EndpointResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of ECA endpoint events.
 *
 * @EcaEvent(
 *   id = "eca_endpoint",
 *   deriver = "Drupal\eca_endpoint\Plugin\ECA\Event\EndpointEventDeriver"
 * )
 */
class EndpointEvent extends EventBase {

  /**
   * The endpoint base path.
   *
   * @var string
   */
  protected string $endpointBasePath;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaPluginBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->endpointBasePath = $container->getParameter('eca_endpoint.base_path');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $definitions = [];
    $definitions['response'] = [
      'label' => 'ECA Endpoint response',
      'event_name' => EndpointEvents::RESPONSE,
      'event_class' => EndpointResponseEvent::class,
      'tags' => Tag::RUNTIME,
    ];
    $definitions['access'] = [
      'label' => 'ECA Endpoint access',
      'event_name' => EndpointEvents::ACCESS,
      'event_class' => EndpointAccessEvent::class,
      'tags' => Tag::RUNTIME | Tag::BEFORE,
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'first_path_argument' => '',
      'second_path_argument' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['first_path_argument'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First path argument'),
      '#default_value' => $this->configuration['first_path_argument'],
      '#description' => $this->t('The <strong>first</strong> path argument to match up. This argument will be resolved from the URL path <em>/eca/<strong>{first}</strong>/{second}</em>.'),
      '#required' => TRUE,
      '#weight' => 10,
    ];
    $form['second_path_argument'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second path argument'),
      '#default_value' => $this->configuration['second_path_argument'],
      '#description' => $this->t('Optionally specify a second path argument to match up. This argument will be resolved from the URL path <em>/eca/{first}/<strong>{second}</strong></em>.'),
      '#required' => FALSE,
      '#weight' => 20,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['first_path_argument'] = $form_state->getValue('first_path_argument');
    $this->configuration['second_path_argument'] = $form_state->getValue('second_path_argument');
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    switch ($this->getDerivativeId()) {

      case 'response':
      case 'access':
        $configuration = $ecaEvent->getConfiguration();
        $first_path_argument = trim((string) ($configuration['first_path_argument'] ?? ''));
        $second_path_argument = trim((string) ($configuration['second_path_argument'] ?? ''));
        $wildcard = '';
        $wildcard .= $first_path_argument === '' ? '*' : $first_path_argument;
        $wildcard .= '::';
        $wildcard .= $second_path_argument === '' ? '*' : $second_path_argument;
        return $wildcard;

      default:
        return parent::lazyLoadingWildcard($eca_config_id, $ecaEvent);

    }
  }

}
