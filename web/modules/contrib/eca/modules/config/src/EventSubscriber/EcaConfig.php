<?php

namespace Drupal\eca_config\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_config\Plugin\ECA\Event\ConfigEvent;

/**
 * ECA config event subscriber.
 */
class EcaConfig extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (ConfigEvent::definitions() as $definition) {
      // Call subscribed validate listeners as early as possible, so that we are
      // not affected from stopped event propagation.
      // @see \Drupal\system\SystemConfigSubscriber::getSubscribedEvents()
      $priority = $definition['event_name'] === ConfigEvents::IMPORT_VALIDATE ? 1024 : 0;
      $events[$definition['event_name']][] = ['onEvent', $priority];
    }
    return $events;
  }

}
