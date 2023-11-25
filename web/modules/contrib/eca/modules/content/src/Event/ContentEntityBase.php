<?php

namespace Drupal\eca_content\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Base class for entity related events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
abstract class ContentEntityBase extends Event implements ConditionalApplianceInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return TRUE;
  }

}
