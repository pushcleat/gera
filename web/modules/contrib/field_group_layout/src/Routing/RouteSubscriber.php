<?php

namespace Drupal\field_group_layout\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\field_group_layout\Form\FieldGroupAddFormOverride;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field group routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Create fieldgroup routes for every entity.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Routes to add field groups.
      $names = [
        "field_ui.field_group_add_$entity_type_id.form_display",
        "field_ui.field_group_add_$entity_type_id.form_display.form_mode",
        "field_ui.field_group_add_$entity_type_id.display",
      ];
      foreach ($names as $name) {
        $route = $collection->get($name);
        if ($route instanceof Route) {
          $route->setDefault('_form', FieldGroupAddFormOverride::class);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Come after field_ui, config_translation and field_group.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -211];
    return $events;
  }

}
