<?php

namespace Drupal\eca\Event;

/**
 * Interface for events that can determine appliance on their own.
 *
 * This is for events extending from\Drupal\Component\EventDispatcher\Event.
 */
interface ConditionalApplianceInterface {

  /**
   * Whether a given ECA event model is configured to react upon this event.
   *
   * @param string $id
   *   The ID of the modeler config. This identifies the configured event
   *   within an ECA configuration.
   * @param array $arguments
   *   The modeler event arguments, which is usually the configuration array
   *   of the modeled event.
   *
   * @return bool
   *   Returns TRUE when this event applies, FALSE otherwise.
   */
  public function applies(string $id, array $arguments): bool;

  /**
   * Whether a given wildcard matches up with this event.
   *
   * This method may always return TRUE when it cannot determine its appliance
   * using a wildcard string. Having the same circumstances, it must never
   * return FALSE when ::applies would return TRUE.
   *
   * @return bool
   *   Returns TRUE when this event applies, FALSE otherwise.
   *
   * @see \Drupal\eca\Plugin\ECA\Event\EventInterface::lazyLoadingWildcard
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool;

}
