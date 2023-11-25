<?php

namespace Drupal\eca\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase as CoreActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ECA provided actions.
 */
abstract class ActionBase extends CoreActionBase implements ContainerFactoryPluginInterface, ActionInterface {

  /**
   * Triggered event leading to this action.
   *
   * @var \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event
   */
  protected object $event;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The ECA-related token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * ECA state service.
   *
   * @var \Drupal\eca\EcaState
   */
  protected EcaState $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TokenInterface $token_services, AccountProxyInterface $current_user, TimeInterface $time, EcaState $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenServices = $token_services;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->state = $state;

    if ($this instanceof ConfigurableInterface) {
      $this->setConfiguration($configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('eca.state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEvent(object $event): ActionInterface {
    $this->event = $event;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent(): object {
    return $this->event;
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
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
