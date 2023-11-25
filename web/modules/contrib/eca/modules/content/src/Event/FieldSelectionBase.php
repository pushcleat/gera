<?php

namespace Drupal\eca_content\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityEventInterface;

/**
 * Base class for field selection events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
abstract class FieldSelectionBase extends Event implements ConditionalApplianceInterface, EntityEventInterface {}
