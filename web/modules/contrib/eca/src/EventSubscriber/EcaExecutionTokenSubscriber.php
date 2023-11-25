<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Event\TokenReceiverInterface;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prepares and cleans up the Token service when executing ECA logic.
 */
class EcaExecutionTokenSubscriber implements EventSubscriberInterface {

  /**
   * The Token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * The EcaExecutionTokenSubscriber constructor.
   *
   * @param \Drupal\eca\Token\TokenInterface $token_services
   *   The Token services.
   */
  public function __construct(TokenInterface $token_services) {
    $this->tokenServices = $token_services;
  }

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $event) {
    // Determine explicitly defined tokens to be forwarded.
    $forwardTokens = [];
    $triggeredEvent = $event->getEvent();
    if ($triggeredEvent instanceof TokenReceiverInterface) {
      foreach ($triggeredEvent->getTokenNamesToReceive() as $key) {
        if ($this->tokenServices->hasTokenData($key)) {
          $forwardTokens[$key] = $this->tokenServices->getTokenData($key);
        }
      }
    }

    // The following block resets the data state of the Token services, with an
    // exception for explicitly defined Tokens to be forwarded. This reset step
    // is necessary, so that variables are only available within their scope.
    $token_data = $this->tokenServices->getTokenData();
    $event->setPrestate('token_data', $token_data);
    $this->tokenServices->clearTokenData();
    foreach ($forwardTokens as $key => $value) {
      $this->tokenServices->addTokenData($key, $value);
    }
  }

  /**
   * Subscriber method after initial execution.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $event
   *   The according event.
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $event) {
    // Determine explicitly defined tokens to be received back.
    $receiveTokens = [];
    $triggeredEvent = $event->getEvent();
    if ($triggeredEvent instanceof TokenReceiverInterface) {
      foreach ($triggeredEvent->getTokenNamesToReceive() as $key) {
        if ($this->tokenServices->hasTokenData($key)) {
          $receiveTokens[$key] = $this->tokenServices->getTokenData($key);
        }
      }
    }

    // Clear the Token data once more, and restore the state of Token data
    // for the wrapping logic (if any). Doing so prevents locally scoped Tokens
    // from unintentionally breaking out.
    $this->tokenServices->clearTokenData();
    $token_data = $event->getPrestate('token_data') ?? [];
    foreach ($token_data as $key => $data) {
      $this->tokenServices->addTokenData($key, $data);
    }
    foreach ($receiveTokens as $key => $value) {
      $this->tokenServices->addTokenData($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = [
      'onBeforeInitialExecution',
      1000,
    ];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = [
      'onAfterInitialExecution',
      -1000,
    ];
    return $events;
  }

}
