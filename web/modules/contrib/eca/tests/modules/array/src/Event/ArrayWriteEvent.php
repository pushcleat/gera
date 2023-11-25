<?php

namespace Drupal\eca_test_array\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Provides an array write event.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package eca_test_array
 */
class ArrayWriteEvent extends Event implements ConditionalApplianceInterface {

  /**
   * The key that was written to the array.
   *
   * @var string
   */
  public string $key;

  /**
   * The value that was written to the array.
   *
   * @var string
   */
  public string $value;

  /**
   * Constructs a new ArrayWriteEvent object.
   *
   * @param string $key
   *   The key that was written to the array.
   * @param string $value
   *   The according value.
   */
  public function __construct(string $key, string $value) {
    $this->key = $key;
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$key, $value] = explode('::', $wildcard, 2);
    return ($this->key === '*' || $this->key === $key) && ($this->value === '*' || $this->value === $value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if ($arguments['key'] !== '' && $arguments['key'] !== '*' && $arguments['key'] !== $this->key) {
      return FALSE;
    }
    if ($arguments['value'] !== '' && $arguments['value'] !== '*' && $arguments['key'] !== $this->key) {
      return FALSE;
    }
    return TRUE;
  }

}
