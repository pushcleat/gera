<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\eca\Processor;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base ECA event subscriber.
 */
abstract class EcaBase implements EventSubscriberInterface {

  /**
   * ECA processor service.
   *
   * @var \Drupal\eca\Processor
   */
  protected Processor $processor;

  /**
   * ECA token service.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenService;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * ContentEntity constructor.
   *
   * @param \Drupal\eca\Processor $processor
   *   ECA processor service.
   * @param \Drupal\eca\Token\TokenInterface $token_service
   *   ECA token service.
   */
  public function __construct(Processor $processor, TokenInterface $token_service) {
    $this->processor = $processor;
    $this->tokenService = $token_service;
  }

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager): EcaBase {
    $this->entityTypeManager = $entityTypeManager;
    return $this;
  }

  /**
   * Callback handling all events subscribed by ECA (sub-)modules.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The triggered event that gets processed by the ECA processor.
   * @param string $event_name
   *   The specific event name that got triggered for that event.
   */
  public function onEvent(object $event, string $event_name): void {
    try {
      if (!Settings::get('eca_disable', FALSE)) {
        $this->processor->execute($this->prepareEvent($event, $event_name), $event_name);
      }
      elseif (\Drupal::currentUser()->hasPermission('administer eca')) {
        \Drupal::messenger()->addWarning('ECA is disabled in your settings.php file.');
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // This is thrown during installation of eca and we can ignore this.
    }
  }

  /**
   * Prepares the given event for being forwarded to the ECA processor.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The triggered event that gets processed by the ECA processor.
   * @param string &$event_name
   *   The specific event name that got triggered for that event, passed by
   *   reference.
   *
   * @return \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event
   *   The prepared event. Can be an object other than the one that got passed
   *   as parameter.
   */
  protected function prepareEvent(object $event, string &$event_name): object {
    return $event;
  }

}
