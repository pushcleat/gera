<?php

namespace Drupal\eca_form\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_form\Plugin\ECA\Event\FormEvent;

/**
 * ECA event subscriber regarding form events.
 */
class EcaForm extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (FormEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
