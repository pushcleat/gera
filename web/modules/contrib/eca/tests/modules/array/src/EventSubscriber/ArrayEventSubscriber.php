<?php

namespace Drupal\eca_test_array\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase as EcaBaseSubscriber;
use Drupal\eca_test_array\Plugin\ECA\Event\ArrayEvent;

/**
 * ECA array event subscriber.
 */
class ArrayEventSubscriber extends EcaBaseSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (ArrayEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
