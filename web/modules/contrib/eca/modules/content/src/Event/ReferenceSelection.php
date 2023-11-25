<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eca_content\Plugin\EntityReferenceSelection\EventBasedSelection;
use Drupal\eca\Service\ContentEntityTypes;

/**
 * Dispatches on event-based entity reference selection.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class ReferenceSelection extends FieldSelectionBase {

  /**
   * A stack of selection event instances.
   *
   * An instance will be removed by
   * \Drupal\eca_content\Plugin\ECA\Event\ContentEntityEvent::cleanupAfterSuccessors.
   *
   * @var \Drupal\eca_content\Event\ReferenceSelection[]
   */
  public static array $instances = [];

  /**
   * The selection plugin instance.
   *
   * @var \Drupal\eca_content\Plugin\EntityReferenceSelection\EventBasedSelection
   */
  public EventBasedSelection $selection;

  /**
   * Constructs a new ReferenceSelection object.
   *
   * @param \Drupal\eca_content\Plugin\EntityReferenceSelection\EventBasedSelection $selection
   *   The selection plugin instance.
   */
  public function __construct(EventBasedSelection $selection) {
    $this->selection = $selection;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $config = $this->selection->getConfiguration();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $config['entity'] ?? NULL;
    $field_name = $config['field_name'] ?? NULL;
    if (empty($config['target_type']) || !$entity || !$field_name) {
      // Can't do anything without an entity and without a specified field.
      return FALSE;
    }

    if (!ContentEntityTypes::get()->bundleFieldApplies($entity, $arguments['type'])) {
      return FALSE;
    }
    if (!empty($arguments['field_name']) && (trim($arguments['field_name']) !== $field_name)) {
      return FALSE;
    }

    array_push(self::$instances, $this);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $config = $this->selection->getConfiguration();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $config['entity'] ?? NULL;
    $field_name = $config['field_name'] ?? NULL;
    if (!$entity || !$field_name) {
      // Can't do anything without an entity and without a specified field.
      return FALSE;
    }

    $candidates = ['*::*::*'];
    $candidates[] = '*::*::' . trim($field_name);
    $candidates[] = $entity->getEntityTypeId() . '::*::*';
    $candidates[] = $entity->getEntityTypeId() . '::' . $entity->bundle() . '::*';
    $candidates[] = $entity->getEntityTypeId() . '::*::' . trim($field_name);
    $candidates[] = $entity->getEntityTypeId() . '::' . $entity->bundle() . '::' . trim($field_name);
    return in_array($wildcard, $candidates);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->selection->getConfiguration()['entity'];
  }

}
