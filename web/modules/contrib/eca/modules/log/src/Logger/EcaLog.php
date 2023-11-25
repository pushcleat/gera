<?php

namespace Drupal\eca_log\Logger;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\eca_log\Event\LogMessageEvent;
use Drupal\eca_log\LogEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trigger an ECA LogMessageEvent for each created log message.
 */
class EcaLog implements LoggerInterface {

  use RfcLoggerTrait;
  use DependencySerializationTrait;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Construct the EcaLog class.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    if (is_string($level)) {
      $level = $this->levelTranslation[$level];
    }
    $event = new LogMessageEvent($level, $message, $context);
    $this->eventDispatcher->dispatch($event, LogEvents::MESSAGE);
  }

}
