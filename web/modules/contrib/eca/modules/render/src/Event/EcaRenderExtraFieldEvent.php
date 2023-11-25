<?php

namespace Drupal\eca_render\Event;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityApplianceTrait;
use Drupal\eca\Event\EntityEventInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_render\RenderEvents;

/**
 * Dispatched when an extra field is being rendered via ECA.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_render\Event
 */
class EcaRenderExtraFieldEvent extends EcaRenderEventBase implements ConditionalApplianceInterface, DataProviderInterface, EntityEventInterface {

  use EntityApplianceTrait;

  /**
   * The entity in scope.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The machine name of the extra field.
   *
   * @var string
   */
  protected string $extraFieldName;

  /**
   * The options of the display component.
   *
   * @var array
   */
  protected array $options;

  /**
   * The render array build.
   *
   * @var array
   */
  protected array $build;

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected EntityDisplayInterface $display;

  /**
   * The display mode.
   *
   * @var string
   */
  protected string $viewMode;

  /**
   * The display type, either one of "display" or "form".
   *
   * @var string
   */
  protected string $displayType;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EcaRenderExtraFieldEvent object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in scope.
   * @param string $extra_field_name
   *   The machine name of the extra field.
   * @param array $options
   *   The options of the display component.
   * @param array &$build
   *   The render array build.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $view_mode
   *   The display mode.
   * @param string $display_type
   *   The display type, either one of "display" or "form".
   */
  public function __construct(EntityInterface $entity, string $extra_field_name, array $options, array &$build, EntityDisplayInterface $display, string $view_mode, string $display_type) {
    $this->entity = $entity;
    $this->extraFieldName = $extra_field_name;
    $this->options = $options;
    $this->build = &$build;
    $this->display = $display;
    $this->viewMode = $view_mode;
    $this->displayType = $display_type;
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
          'machine-name' => RenderEvents::EXTRA_FIELD,
          'extra-field-name' => $this->extraFieldName,
          'options' => $this->options,
          'entity' => $this->entity,
          'display' => $this->display,
          'mode' => $this->viewMode,
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

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_display_type, $w_extra_field_name, $w_entity_type_ids, $w_bundles] = explode(':', $wildcard);

    if ($w_display_type !== $this->displayType) {
      return FALSE;
    }

    if ($w_extra_field_name !== $this->extraFieldName) {
      return FALSE;
    }

    if (($w_entity_type_ids !== '*') && !in_array($this->getEntity()->getEntityTypeId(), explode(',', $w_entity_type_ids), TRUE)) {
      return FALSE;
    }

    if (($w_bundles !== '*') && !in_array($this->getEntity()->bundle(), explode(',', $w_bundles), TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if (($arguments['display_type'] ?? NULL) !== $this->displayType) {
      return FALSE;
    }

    if (($arguments['extra_field_name'] ?? NULL) !== $this->extraFieldName) {
      return FALSE;
    }

    return $this->appliesForEntityTypeOrBundle($this->getEntity(), $arguments);
  }

}
