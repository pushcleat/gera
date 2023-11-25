<?php

namespace Drupal\eca_render\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase as EcaBaseSubscriber;
use Drupal\eca_render\Plugin\ECA\Event\RenderEvent;

/**
 * ECA Render event subscriber.
 */
class EcaRender extends EcaBaseSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (RenderEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
