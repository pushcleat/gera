<?php

namespace Drupal\eca_access\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca_access\AccessEvents;

/**
 * Dispatched when an entity field is being asked for access.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class FieldAccess extends EntityAccess {

  /**
   * The field name.
   *
   * @var string
   */
  protected string $fieldName;

  /**
   * Constructs a new FieldAccess object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being asked for access.
   * @param string $operation
   *   The operation to perform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that asks for access.
   * @param string $field_name
   *   The field name.
   */
  public function __construct(EntityInterface $entity, string $operation, AccountInterface $account, string $field_name) {
    parent::__construct($entity, $operation, $account);
    $this->fieldName = $field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $parts = explode(':', $wildcard);
    $w_field_names = end($parts);
    if (($w_field_names !== '*') && !in_array($this->getFieldName(), explode(',', $w_field_names), TRUE)) {
      return FALSE;
    }
    return parent::appliesForLazyLoadingWildcard($wildcard);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if (!empty($arguments['field_name']) && $arguments['field_name'] !== '*') {
      $contains_field_name = FALSE;
      foreach (explode(',', $arguments['field_name']) as $c_field_name) {
        $c_field_name = trim($c_field_name);
        if ($contains_field_name = ($c_field_name === $this->getFieldName())) {
          break;
        }
      }
      if (!$contains_field_name) {
        return FALSE;
      }
    }
    return parent::applies($id, $arguments);
  }

  /**
   * Get the field name.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName(): string {
    return $this->fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key): ?DataTransferObject {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $data = [
          'machine-name' => AccessEvents::FIELD,
          'operation' => $this->getOperation(),
          'uid' => $this->getAccount()->id(),
          'field' => $this->getFieldName(),
          'entity-type' => $this->entity->getEntityTypeId(),
          'entity-bundle' => $this->entity->bundle(),
        ];
        if (!$this->entity->isNew()) {
          $data['entity-id'] = $this->entity->id();
        }
        $this->eventData = DataTransferObject::create($data);
      }

      return $this->eventData;
    }

    return NULL;
  }

}
