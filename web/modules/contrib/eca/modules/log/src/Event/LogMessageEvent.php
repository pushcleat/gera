<?php

namespace Drupal\eca_log\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Provides an event when a log message is being submitted.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_log\Event
 */
class LogMessageEvent extends Event implements ConditionalApplianceInterface {

  /**
   * Log message severity.
   *
   * @var int
   */
  protected int $severity;

  /**
   * Log message.
   *
   * @var string
   */
  protected string $message;

  /**
   * Log message context.
   *
   * @var array
   */
  protected array $context;

  /**
   * Construct a LogMessageEvent.
   *
   * @param int $severity
   *   Log message severity.
   * @param string $message
   *   Log message.
   * @param array $context
   *   Log message context.
   */
  public function __construct(int $severity, string $message, array $context) {
    $this->severity = $severity;
    $this->message = $message;
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return (($arguments['channel'] === '') || ($arguments['channel'] === $this->context['channel'])) && $this->severity <= $arguments['min_severity'];
  }

  /**
   * Get the severity.
   *
   * @return int
   *   The severity.
   */
  public function getSeverity(): int {
    return $this->severity;
  }

  /**
   * Get the message.
   *
   * @return string
   *   The message.
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * Get the context.
   *
   * @return array
   *   The context.
   */
  public function getContext(): array {
    return $this->context;
  }

}
