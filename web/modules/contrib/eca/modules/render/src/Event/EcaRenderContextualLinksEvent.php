<?php

namespace Drupal\eca_render\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityApplianceTrait;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_render\RenderEvents;

/**
 * Dispatched when contextual links are being rendered.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_render\Event
 */
class EcaRenderContextualLinksEvent extends EcaRenderEventBase implements ConditionalApplianceInterface, DataProviderInterface {

  use EntityApplianceTrait;

  /**
   * The current links array.
   *
   * @var array
   */
  protected array $links;

  /**
   * The link group.
   *
   * @var string
   */
  protected string $group;

  /**
   * The route parameters.
   *
   * @var array
   */
  protected array $routeParameters;

  /**
   * The render array build.
   *
   * @var array
   */
  protected array $build;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new EcaRenderContextualLinksEvent object.
   *
   * @param array &$links
   *   The current links array.
   * @param string $group
   *   The link group.
   * @param array $route_parameters
   *   The route parameters.
   * @param array &$build
   *   The render array build.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array &$links, string $group, array $route_parameters, array &$build, EntityTypeManagerInterface $entity_type_manager) {
    $this->links = &$links;
    $this->group = $group;
    $this->routeParameters = $route_parameters;
    $this->build = &$build;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function &getRenderArray(): array {
    return $this->build;
  }

  /**
   * Get the current links array.
   *
   * @return array
   *   The links array.
   */
  public function &getLinks(): array {
    return $this->links;
  }

  /**
   * Get the link group.
   *
   * @return string
   *   The link group.
   */
  public function getGroup(): string {
    return $this->group;
  }

  /**
   * Get the route parameters.
   *
   * @return array
   *   The route parameters.
   */
  public function getRouteParameters(): array {
    return $this->routeParameters;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_group, $w_entity_type_ids, $w_bundles] = explode(':', $wildcard, 3);

    if (($w_group !== '*') && !in_array($this->group, explode(',', $w_group), TRUE)) {
      return FALSE;
    }

    if ($w_entity_type_ids !== '*') {
      if (!($entity = $this->getEntity())) {
        return FALSE;
      }
      if (!in_array($entity->getEntityTypeId(), explode(',', $w_entity_type_ids), TRUE)) {
        return FALSE;
      }
    }

    if ($w_bundles !== '*') {
      if (!($entity = $this->getEntity())) {
        return FALSE;
      }
      if (!in_array($entity->bundle(), explode(',', $w_bundles), TRUE)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if (!empty($arguments['group']) && $arguments['group'] !== '*') {
      $contains_group = FALSE;
      foreach (explode(',', $arguments['group']) as $c_group) {
        $c_group = mb_strtolower(trim($c_group));
        if ($contains_group = ($c_group === mb_strtolower($this->group))) {
          break;
        }
      }
      if (!$contains_group) {
        return FALSE;
      }
    }

    if (!($entity = $this->getEntity())) {
      return empty($arguments['entity_type_id']) && empty($arguments['bundle']);
    }

    return $this->appliesForEntityTypeOrBundle($entity, $arguments);
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
          'machine-name' => RenderEvents::CONTEXTUAL_LINKS,
          'group' => $this->group,
          'route-parameters' => $this->routeParameters,
        ]);
      }

      return $this->eventData;
    }

    if (isset($this->routeParameters[$key])) {
      $v = $this->routeParameters[$key];
      if (is_string($key) && $this->entityTypeManager->hasDefinition($key) && is_scalar($v) && ($entity = $this->entityTypeManager->getStorage($key)->load($v))) {
        return $entity;
      }
      return DataTransferObject::create($v);
    }

    return NULL;
  }

  /**
   * Get the entity, if available.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or NULL if not available.
   */
  public function getEntity(): ?EntityInterface {
    foreach ($this->routeParameters as $k => $v) {
      if (is_string($k) && $this->entityTypeManager->hasDefinition($k) && is_scalar($v) && ($entity = $this->entityTypeManager->getStorage($k)->load($v))) {
        return $entity;
      }
    }
    return NULL;
  }

}
