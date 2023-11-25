<?php

namespace Drupal\eca_render\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityEventInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_render\Plugin\views\field\EcaRender;
use Drupal\eca_render\RenderEvents;

/**
 * Dispatched when an ECA Views field is being rendered.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_render\Event
 */
class EcaRenderViewsFieldEvent extends EcaRenderEventBase implements ConditionalApplianceInterface, DataProviderInterface, EntityEventInterface {

  /**
   * The render array build.
   *
   * @var array
   */
  protected array $build;

  /**
   * The field plugin.
   *
   * @var \Drupal\eca_render\Plugin\views\field\EcaRender
   */
  protected EcaRender $fieldPlugin;

  /**
   * The main entity of the Views row.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * Relationship entities of the Views row.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected array $relationshipEntities;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EcaRenderViewsFieldEvent object.
   *
   * @param \Drupal\eca_render\Plugin\views\field\EcaRender $field_plugin
   *   The field plugin.
   * @param array &$build
   *   The render array build.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The main entity of the Views row.
   * @param \Drupal\Core\Entity\EntityInterface[] $relationship_entities
   *   Relationship entities of the Views row.
   */
  public function __construct(EcaRender $field_plugin, array &$build, EntityInterface $entity, array $relationship_entities) {
    $this->fieldPlugin = $field_plugin;
    $this->build = &$build;
    $this->entity = $entity;
    $this->relationshipEntities = $relationship_entities;
  }

  /**
   * Get the field plugin.
   *
   * @return \Drupal\eca_render\Plugin\views\field\EcaRender
   *   The field plugin.
   */
  public function getFieldPlugin(): EcaRender {
    return $this->fieldPlugin;
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
  public function getData(string $key) {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $this->eventData = DataTransferObject::create([
          'machine-name' => RenderEvents::VIEWS_FIELD,
          'entity' => $this->entity,
          'relationships' => $this->relationshipEntities,
          'view-id' => $this->fieldPlugin->view->id(),
          'view-display' => $this->fieldPlugin->view->current_display ?? NULL,
        ]);
      }

      return $this->eventData;
    }

    if ($key === 'entity' || $key === $this->entity->getEntityTypeId()) {
      return $this->entity;
    }

    foreach ($this->relationshipEntities as $i => $entity) {
      if ($key === $i || $key === $entity->getEntityTypeId()) {
        return $entity;
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
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the relationship entities of the Views row.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Relationship entities.
   */
  public function getRelationshipEntities(): array {
    return $this->relationshipEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return (($wildcard === '*') || (($this->fieldPlugin->options['name'] ?? '*') === $wildcard));
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return (($this->fieldPlugin->options['name'] ?? '*') === ($arguments['name'] ?? '*'));
  }

}
