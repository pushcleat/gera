<?php

namespace Drupal\eca_render\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\RenderEventInterface;

/**
 * Base class of ECA render events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_render\Event
 */
abstract class EcaRenderEventBase extends Event implements RenderEventInterface {}
