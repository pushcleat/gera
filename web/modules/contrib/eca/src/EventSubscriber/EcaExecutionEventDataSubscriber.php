<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Token\DataProviderInterface;

/**
 * Adds an event to the Token service in case it's a Token data provider.
 */
class EcaExecutionEventDataSubscriber extends EcaBase {

  /**
   * A stack of events providing Token data.
   *
   * @var \Drupal\eca\Token\DataProviderInterface[]
   */
  protected array $eventStack = [];

  /**
   * Subscriber method before initial execution.
   *
   * Adds the event as data provider to the Token service.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof DataProviderInterface) {
      array_unshift($this->eventStack, $event);
      $this->tokenService->addTokenDataProvider($event);
    }
    else {
      // At least provide the event name as a token.
      $this->tokenService->addTokenData('event:machine-name', $before_event->getEventName());
    }
  }

  /**
   * Subscriber method after initial execution.
   *
   * Removes the event as data provider from the Token service.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $after_event
   *   The according event.
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $after_event): void {
    $event = $after_event->getEvent();
    if ($event instanceof DataProviderInterface) {
      array_shift($this->eventStack);
      $this->tokenService->removeTokenDataProvider($event);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = ['onAfterInitialExecution'];
    return $events;
  }

}
