<?php

namespace Drupal\eca_access\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_access\Plugin\ECA\Event\AccessEvent;

/**
 * ECA base event subscriber.
 */
class EcaAccess extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (AccessEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
