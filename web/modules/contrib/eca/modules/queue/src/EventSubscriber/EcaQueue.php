<?php

namespace Drupal\eca_queue\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_queue\Plugin\ECA\Event\QueueEvent;

/**
 * ECA base event subscriber.
 */
class EcaQueue extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (QueueEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
