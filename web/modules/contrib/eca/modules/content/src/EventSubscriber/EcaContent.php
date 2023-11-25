<?php

namespace Drupal\eca_content\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_content\Plugin\ECA\Event\ContentEntityEvent;

/**
 * ECA event subscriber regarding content entity events.
 */
class EcaContent extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (ContentEntityEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
