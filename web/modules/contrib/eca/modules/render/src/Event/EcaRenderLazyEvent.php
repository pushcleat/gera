<?php

namespace Drupal\eca_render\Event;

use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_render\RenderEvents;

/**
 * Dispatched when a lazy ECA element is being rendered.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_render\Event
 */
class EcaRenderLazyEvent extends EcaRenderEventBase implements ConditionalApplianceInterface, DataProviderInterface {

  /**
   * The name that identifies the lazy element for the event.
   *
   * @var string
   */
  public string $name;

  /**
   * An optional argument for rendering the element.
   *
   * @var string
   */
  public string $argument;

  /**
   * The render array build.
   *
   * @var array
   */
  public array $build;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EcaRenderLazyEvent object.
   *
   * @param array &$build
   *   The render array build.
   */
  public function __construct(string $name, string $argument, array &$build) {
    $this->name = $name;
    $this->argument = $argument;
    $this->build = &$build;
  }

  /**
   * {@inheritdoc}
   */
  public function &getRenderArray(): array {
    return $this->build;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return ($this->name === $wildcard) || ($wildcard === '*');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $arg_name = $arguments['name'] ?? '*';
    return ($this->name === $arg_name) || ($arg_name === '*');
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $this->eventData = DataTransferObject::create([
          'machine-name' => RenderEvents::LAZY_ELEMENT,
          'name' => $this->name,
          'argument' => $this->argument,
        ]);
      }

      return $this->eventData;
    }

    if ($key === 'argument') {
      return $this->argument;
    }

    if ($key === 'name') {
      return $this->name;
    }

    return NULL;
  }

}
