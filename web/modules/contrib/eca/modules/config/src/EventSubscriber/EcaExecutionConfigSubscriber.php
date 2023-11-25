<?php

namespace Drupal\eca_config\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\eca\EcaEvents;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\EventSubscriber\EcaBase;

/**
 * Adds the configuration to the Token service when executing ECA logic.
 */
class EcaExecutionConfigSubscriber extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof ConfigCrudEvent) {
      $config = $event->getConfig();
      $this->tokenService->addTokenData('config', $config->get());
      $this->tokenService->addTokenData('config_name', $config->getName());
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
