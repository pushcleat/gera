<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\Core\Session\AccountSetEvent;
use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AccountEventInterface;
use Drupal\eca\Event\BeforeInitialExecutionEvent;

/**
 * Adds the involved account to the Token service when executing ECA logic.
 */
class EcaExecutionAccountSubscriber extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if (($event instanceof AccountEventInterface) || ($event instanceof AccountSetEvent)) {
      if ($user = $this->entityTypeManager->getStorage('user')->load($event->getAccount()->id())) {
        $this->tokenService->addTokenData('account', $user);
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
