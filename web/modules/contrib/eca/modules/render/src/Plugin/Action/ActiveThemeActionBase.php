<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for actions related to the active theme.
 */
abstract class ActiveThemeActionBase extends ConfigurableActionBase {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The theme initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected ThemeInitializationInterface $themeInitialization;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_render\Plugin\Action\ActiveThemeActionBase $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->themeManager = $container->get('theme.manager');
    $instance->themeHandler = $container->get('theme_handler');
    $instance->themeInitialization = $container->get('theme.initialization');
    return $instance;
  }

}
