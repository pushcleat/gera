<?php

namespace Drupal\eca_base\Commands;

use Drupal\eca_base\BaseEvents;
use Drupal\eca_base\Event\CustomEvent;
use Drush\Commands\DrushCommands;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Drush commandfile.
 */
class EcaBaseCommands extends DrushCommands {

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * EcaBaseCommand constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    parent::__construct();
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Trigger custom event with given event ID.
   *
   * @param string $id
   *   The id of the custom event to be triggered.
   *
   * @usage eca:trigger:custom_event
   *   Trigger custom event with given event ID.
   *
   * @command eca:trigger:custom_event
   */
  public function triggerCustomEvent(string $id): void {
    $event = new CustomEvent($id, []);
    $this->eventDispatcher->dispatch($event, BaseEvents::CUSTOM);
  }

}
