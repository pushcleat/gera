<?php

namespace Drupal\eca_base\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\CleanupInterface;
use Drupal\eca\Token\ContextDataProvider;

/**
 * Set currently defined token data to be available for any child process.
 *
 * @Action(
 *   id = "eca_token_set_context",
 *   label = @Translation("Token: set context"),
 *   description = @Translation("Set currently defined token data to be available for any child process.")
 * )
 */
class TokenSetContext extends ConfigurableActionBase implements CleanupInterface {

  /**
   * The context data provider.
   *
   * @var \Drupal\eca\Token\ContextDataProvider
   */
  protected ContextDataProvider $contextDataProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_base\Plugin\Action\TokenSetContext $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setContextDataProvider($container->get('eca.token_data.context'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $data = $this->tokenServices->getTokenData();
    $this->contextDataProvider->push($data);
  }

  /**
   * Set the context data provider.
   *
   * @var \Drupal\eca\Token\ContextDataProvider $provider
   *   The provider.
   */
  public function setContextDataProvider(ContextDataProvider $provider): void {
    $this->contextDataProvider = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupAfterSuccessors(): void {
    $this->contextDataProvider->pop();
  }

}
