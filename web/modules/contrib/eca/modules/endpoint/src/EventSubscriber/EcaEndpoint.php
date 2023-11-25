<?php

namespace Drupal\eca_endpoint\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase as EcaBaseSubscriber;
use Drupal\eca_endpoint\Plugin\ECA\Event\EndpointEvent;

/**
 * ECA Endpoint event subscriber.
 */
class EcaEndpoint extends EcaBaseSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (EndpointEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
