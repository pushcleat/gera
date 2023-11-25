<?php

namespace Drupal\eca_endpoint\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Base class of ECA endpoint events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_endpoint\Event
 */
abstract class EndpointEventBase extends Event implements ConditionalApplianceInterface {

  /**
   * The arguments provided in the URL path.
   *
   * @var array
   */
  public array $pathArguments;

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$first_path_argument, $second_path_argument] = explode('::', $wildcard, 2);
    if (($first_path_argument !== '*') && (reset($this->pathArguments) !== $first_path_argument)) {
      return FALSE;
    }
    if (($second_path_argument !== '*') && (next($this->pathArguments) !== $second_path_argument)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $first_path_argument = trim((string) ($arguments['first_path_argument'] ?? '*'));
    $second_path_argument = trim((string) ($arguments['second_path_argument'] ?? '*'));
    if (!in_array($first_path_argument, ['', '*'], TRUE) && (reset($this->pathArguments) !== $first_path_argument)) {
      return FALSE;
    }
    if (!in_array($second_path_argument, ['', '*'], TRUE) && (next($this->pathArguments) !== $second_path_argument)) {
      return FALSE;
    }
    return TRUE;
  }

}
