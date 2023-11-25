<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\EntityEventInterface;
use Drupal\eca\Service\ContentEntityTypes;

/**
 * Base class for content entity related events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
abstract class ContentEntityBaseEntity extends ContentEntityBase implements EntityEventInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The entity type service.
   *
   * @var \Drupal\eca\Service\ContentEntityTypes
   */
  protected ContentEntityTypes $entityTypes;

  /**
   * ContentEntityBaseEntity constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\eca\Service\ContentEntityTypes $entity_types
   *   The entity type service.
   */
  public function __construct(ContentEntityInterface $entity, ContentEntityTypes $entity_types) {
    $entity->eca_context = TRUE;
    $this->entity = $entity;
    $this->entityTypes = $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $entity = $this->getEntity();
    return in_array($wildcard, [
      '*',
      $entity->getEntityTypeId(),
      $entity->getEntityTypeId() . '::' . $entity->bundle(),
    ], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return $this->entityTypes->bundleFieldApplies($this->getEntity(), $arguments['type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
