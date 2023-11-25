<?php

namespace Drupal\eca_access\Event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\AccessEventInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityApplianceTrait;
use Drupal\eca\Event\EntityEventInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_access\AccessEvents;

/**
 * Dispatched when an entity is being asked for access.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class EntityAccess extends Event implements AccessEventInterface, ConditionalApplianceInterface, EntityEventInterface, DataProviderInterface {

  use EntityApplianceTrait;

  /**
   * The entity being asked for access.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The operation to perform.
   *
   * @var string
   */
  protected string $operation;

  /**
   * The account that asks for access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResultInterface|null
   */
  protected ?AccessResultInterface $accessResult = NULL;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EntityAccess object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being asked for access.
   * @param string $operation
   *   The operation to perform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that asks for access.
   */
  public function __construct(EntityInterface $entity, string $operation, AccountInterface $account) {
    $this->entity = $entity;
    $this->operation = $operation;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the operation to perform.
   *
   * @return string
   *   The operation.
   */
  public function getOperation(): string {
    return $this->operation;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(): AccountInterface {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_entity_type_ids, $w_bundles, $w_operations] = explode(':', $wildcard);

    if (($w_entity_type_ids !== '*') && !in_array($this->getEntity()->getEntityTypeId(), explode(',', $w_entity_type_ids), TRUE)) {
      return FALSE;
    }

    if (($w_bundles !== '*') && !in_array($this->getEntity()->bundle(), explode(',', $w_bundles), TRUE)) {
      return FALSE;
    }

    if (($w_operations !== '*') && !in_array($this->getOperation(), explode(',', $w_operations), TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if (!$this->appliesForEntityTypeOrBundle($this->getEntity(), $arguments)) {
      return FALSE;
    }

    if (!empty($arguments['operation']) && $arguments['operation'] !== '*') {
      $contains_operation = FALSE;
      foreach (explode(',', $arguments['operation']) as $c_operation) {
        $c_operation = trim($c_operation);
        if ($contains_operation = ($c_operation === $this->getOperation())) {
          break;
        }
      }
      if (!$contains_operation) {
        return FALSE;
      }
    }

    // Initialize with a neutral result.
    $this->accessResult = AccessResult::neutral();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessResult(): ?AccessResultInterface {
    return $this->accessResult;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessResult(AccessResultInterface $result): EntityAccess {
    $this->accessResult = $result;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key): ?DataTransferObject {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $data = [
          'machine-name' => AccessEvents::ENTITY,
          'operation' => $this->getOperation(),
          'uid' => $this->getAccount()->id(),
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

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

}
