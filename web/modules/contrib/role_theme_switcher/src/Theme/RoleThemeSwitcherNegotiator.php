<?php

namespace Drupal\role_theme_switcher\Theme;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Class RoleThemeSwitcherNegotiator.
 */
class RoleThemeSwitcherNegotiator implements ThemeNegotiatorInterface {

  /**
   * Protected theme variable to store the theme to active.
   *
   * @var string
   */
  protected $theme = NULL;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Creates a new RoleThemeSwitcherNegotiator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context to determine whether the route is an admin one.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, AdminContext $admin_context) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->adminContext = $admin_context;
  }

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    $roles = $this->configFactory->get('role_theme_switcher.settings')->get('roles');
    if ($roles) {
      $admin_path = $this->adminContext->isAdminRoute($route_match->getRouteObject());

      // Properly order all rows by weight.
      uasort($roles, [
        'Drupal\Component\Utility\SortArray',
        'sortByWeightElement',
      ]);

      $user_roles = $this->currentUser->getRoles();
      foreach ($roles as $rid => $config) {
        if (in_array($rid, $user_roles)) {
          $this->theme = $config['theme'];
          if ($admin_path) {
            $this->theme = $config['admin_theme'];
          }
          break;
        }
      }
    }
    // Return TRUE if there is a theme to activate.
    return (bool) $this->theme;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string
   *   The name of the theme
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

}
