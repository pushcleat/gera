<?php

namespace Drupal\eca_render\Event;

use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_render\Plugin\Block\EcaBlock;
use Drupal\eca_render\RenderEvents;

/**
 * Dispatched when an ECA Block is being rendered.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_render\Event
 */
class EcaRenderBlockEvent extends EcaRenderEventBase implements ConditionalApplianceInterface, DataProviderInterface {

  /**
   * The block plugin instance.
   *
   * @var \Drupal\eca_render\Plugin\Block\EcaBlock
   */
  protected EcaBlock $block;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EcaRenderBlockEvent object.
   *
   * @param \Drupal\eca_render\Plugin\Block\EcaBlock $block
   *   The block plugin instance.
   */
  public function __construct(EcaBlock $block) {
    $this->block = $block;
  }

  /**
   * Get the block plugin instance.
   *
   * @return \Drupal\eca_render\Plugin\Block\EcaBlock
   *   The instance.
   */
  public function getBlock(): EcaBlock {
    return $this->block;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return ($this->block->getDerivativeId() === $wildcard);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return ($this->block->getDerivativeId() === ($arguments['block_machine_name'] ?? ''));
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $this->eventData = DataTransferObject::create([
          'machine-name' => RenderEvents::BLOCK,
        ]);
      }

      return $this->eventData;
    }

    $context_definitions = $this->block->getContextDefinitions();
    if (isset($context_definitions[$key])) {
      $context = $this->block->getContext($key);
      if ($context->hasContextValue()) {
        return $context->getContextValue();
      }
    }

    return NULL;
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
  public function &getRenderArray(): array {
    $build = &$this->block->build;
    return $build;
  }

}
