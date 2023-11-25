<?php

namespace Drupal\eca_workflow\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityEventInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Service\ContentEntityTypes;
use Drupal\eca\Token\DataProviderInterface;

/**
 * Dispatched when a moderation state changed.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class TransitionEvent extends Event implements ConditionalApplianceInterface, EntityEventInterface, DataProviderInterface {

  /**
   * The moderated entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * From state (if given).
   *
   * @var string|null
   */
  protected ?string $fromState;

  /**
   * To state (if given).
   *
   * @var string
   */
  protected string $toState;

  /**
   * The entity types service.
   *
   * @var \Drupal\eca\Service\ContentEntityTypes
   */
  protected ContentEntityTypes $entityTypes;

  /**
   * Constructs a new TransitionEvent object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The moderated entity.
   * @param string|null $from_state
   *   (optional) From state.
   * @param string $to_state
   *   New state.
   * @param \Drupal\eca\Service\ContentEntityTypes $entity_types
   *   The entity types service.
   */
  public function __construct(ContentEntityInterface $entity, ?string $from_state, string $to_state, ContentEntityTypes $entity_types) {
    $this->entity = $entity;
    $this->fromState = $from_state;
    $this->toState = $to_state;
    $this->entityTypes = $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the from state (if available).
   *
   * @return string|null
   *   The from state, or NULL if not available.
   */
  public function getFromState(): ?string {
    return $this->fromState;
  }

  /**
   * Get the new state.
   *
   * @return string
   *   The new state.
   */
  public function getToState(): string {
    return $this->toState;
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
    switch ($key) {

      case 'from_state':
        if (isset($this->fromState)) {
          return DataTransferObject::create($this->fromState);
        }
        return NULL;

      case 'to_state':
        return DataTransferObject::create($this->toState);

      default:
        return NULL;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_entity_type_id, $w_entity_bundle, $w_from_state, $w_to_state] = explode('::', $wildcard);
    $entity = $this->getEntity();
    if (($w_entity_type_id !== '*') && ($w_entity_type_id !== $entity->getEntityTypeId())) {
      return FALSE;
    }
    if (($w_entity_bundle !== '*') && ($w_entity_bundle !== $entity->bundle())) {
      return FALSE;
    }
    if (($w_from_state !== '*') && ($w_from_state !== $this->fromState)) {
      return FALSE;
    }
    if (($w_to_state !== '*') && ($w_to_state !== $this->toState)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $entity = $this->getEntity();
    if (!$this->entityTypes->bundleFieldApplies($entity, $arguments['type'])) {
      return FALSE;
    }
    $from_state = $arguments['from_state'] ?? '';
    if (!in_array($from_state, ['', '*'], TRUE) && ($from_state !== $this->fromState)) {
      return FALSE;
    }
    $to_state = $arguments['to_state'] ?? '';
    if (!in_array($to_state, ['', '*'], TRUE) && ($to_state !== $this->toState)) {
      return FALSE;
    }
    return TRUE;
  }

}
