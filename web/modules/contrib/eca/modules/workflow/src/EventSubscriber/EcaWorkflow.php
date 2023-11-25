<?php

namespace Drupal\eca_workflow\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase as EcaBaseSubscriber;
use Drupal\eca_workflow\Plugin\ECA\Event\WorkflowEvent;

/**
 * ECA Workflow event subscriber.
 */
class EcaWorkflow extends EcaBaseSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (WorkflowEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
