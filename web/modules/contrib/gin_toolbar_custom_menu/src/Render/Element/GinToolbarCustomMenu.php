<?php

namespace Drupal\gin_toolbar_custom_menu\Render\Element;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Gin Toolbar Custom Menu.
 *
 * @package Drupal\gin_toolbar_custom_menu\Render\Element
 */
class GinToolbarCustomMenu implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderTray'];
  }

  /**
   * Renders the toolbar's administration tray.
   *
   * This is a clone of core's toolbar_prerender_toolbar_administration_tray()
   * function, which adds active trail information and which uses setMaxDepth(4)
   * instead of setTopLevelOnly() in case the Admin Toolbar module is installed.
   *
   * @param array $build
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array.
   *
   * @see toolbar_prerender_toolbar_administration_tray()
   */
  public static function preRenderTray(array $build) {
    $build['#cache']['tags'][] = 'gin_toolbar_custom_menu:settings';
  
    $user_roles = \Drupal::currentUser()->getRoles();
    $config = \Drupal::config('gin_toolbar_custom_menu.settings');
  
    if (empty($user_roles) || empty($config->get('menu')) || empty($config->get('role'))) {
      return $build;
    }
  
    $has_role = FALSE;
    foreach ($config->get('role') as $role) {
      if (is_array($user_roles) && in_array($role, array_values($user_roles))) {
        $has_role = TRUE;
      }
    }
  
    if (!$has_role) {
      return $build;
    }
  
    $custom_menu = $config->get('menu');
    if (!$custom_menu) {
      return $build;
    }
  
    $menu_tree = \Drupal::service('toolbar.menu_tree');
    $activeTrail = \Drupal::service('gin_toolbar.active_trail')->getActiveTrailIds($custom_menu);
    $parameters = (new MenuTreeParameters())
      ->setActiveTrail($activeTrail)
      ->setTopLevelOnly()
      ->onlyEnabledLinks();
  
    if (\Drupal::moduleHandler()->moduleExists('admin_toolbar')) {
      $admin_toolbar_settings = \Drupal::config('admin_toolbar.settings');
      $max_depth = $admin_toolbar_settings->get('menu_depth') ?? 4;
      $parameters->setMaxDepth($max_depth);
    }
  
    $tree = $menu_tree->load($custom_menu, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'gin_toolbar_custom_menu_toolbar_menu_navigation_links'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $menu = $menu_tree->build($tree);
  
    // Ensure menu_items is not null & an array.
    $menu_items = isset($menu["#items"]) && is_array($menu["#items"]) ? $menu["#items"] : [];
    
    // Check if the required elements are not null.
    if (isset($build["administration_menu"]["#items"]["admin_toolbar_tools.help"])) {
      $menu_items["admin_toolbar_tools.help"] = $build["administration_menu"]["#items"]["admin_toolbar_tools.help"];
    }
  
    $build["administration_menu"]["#items"] = $menu_items;
    $build['#cache']['contexts'][] = 'route.menu_active_trails:' . $custom_menu;
    $build['#attached']['library'][] = 'gin_toolbar_custom_menu/toolbar';
  
    return $build;
  }
  

}
