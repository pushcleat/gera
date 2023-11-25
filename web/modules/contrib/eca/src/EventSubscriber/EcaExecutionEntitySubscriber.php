<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Event\EntityEventInterface;

/**
 * Adds the event-related entity to the Token service when executing ECA logic.
 */
class EcaExecutionEntitySubscriber extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof EntityEventInterface) {
      $entity = $event->getEntity();
      $this->tokenService->addTokenData('entity', $entity);
      if ($token_type = $this->tokenService->getTokenTypeForEntityType($entity->getEntityTypeId())) {
        $this->tokenService->addTokenData($token_type, $entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    return $events;
  }

}
