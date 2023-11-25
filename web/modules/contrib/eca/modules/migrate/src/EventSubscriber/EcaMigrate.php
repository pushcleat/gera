<?php

namespace Drupal\eca_migrate\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_migrate\Plugin\ECA\Event\MigrateEvent;

/**
 * ECA migrate event subscriber.
 */
class EcaMigrate extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (MigrateEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
