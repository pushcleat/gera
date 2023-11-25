<?php

namespace Drupal\eca_content\Event;

use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;

/**
 * Provides an event before a content entity is being loaded.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityPreLoad extends ContentEntityBase implements DataProviderInterface {

  /**
   * The ids.
   *
   * @var array
   */
  protected array $ids;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructor.
   *
   * @param array $ids
   *   The ids.
   * @param string $entity_type_id
   *   The entity type id.
   */
  public function __construct(array $ids, string $entity_type_id) {
    $this->ids = $ids;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * Gets the ids.
   *
   * @return array
   *   The ids.
   */
  public function getIds(): array {
    return $this->ids;
  }

  /**
   * Gets the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return in_array($wildcard, ['*', $this->entityTypeId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $this->eventData = DataTransferObject::create([
          'machine-name' => ContentEntityEvents::PRELOAD,
          'entity_type_id' => $this->getEntityTypeId(),
          'ids' => $this->getIds(),
        ]);
      }

      return $this->eventData;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

}
