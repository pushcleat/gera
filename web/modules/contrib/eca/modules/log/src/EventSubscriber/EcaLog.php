<?php

namespace Drupal\eca_log\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_log\Plugin\ECA\Event\LogEvent;

/**
 * ECA log event subscriber.
 */
class EcaLog extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (LogEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
