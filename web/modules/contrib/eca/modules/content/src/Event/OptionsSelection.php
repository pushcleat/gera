<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\eca\Service\ContentEntityTypes;

/**
 * Dispatches on event-based options selection.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class OptionsSelection extends FieldSelectionBase {

  /**
   * A stack of selection event instances.
   *
   * An instance will be removed by
   * \Drupal\eca_content\Plugin\ECA\Event\ContentEntityEvent::cleanupAfterSuccessors.
   *
   * @var \Drupal\eca_content\Event\OptionsSelection[]
   */
  public static array $instances = [];

  /**
   * The field storage definition.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  public FieldStorageDefinitionInterface $fieldStorageDefinition;

  /**
   * The according entity.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface|null
   */
  public ?FieldableEntityInterface $entity;

  /**
   * The current list of allowed values.
   *
   * @var array
   */
  public array $allowedValues;

  /**
   * Constructs a new event.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   The according entity.
   * @param array $allowed_values
   *   The current list of allowed values.
   */
  public function __construct(FieldStorageDefinitionInterface $field_storage_definition, ?FieldableEntityInterface $entity, array $allowed_values) {
    $this->fieldStorageDefinition = $field_storage_definition;
    $this->entity = $entity;
    $this->allowedValues = $allowed_values;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->entity ?? NULL;
    $field_name = $this->fieldStorageDefinition->getName();
    if (!$entity || !$field_name) {
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
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->entity ?? NULL;
    $field_name = $this->fieldStorageDefinition->getName();
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
    return $this->entity;
  }

}
