<?php

/**
 * @file
 * Contains \Drupal\theme_system_sandbox\Theme\RegistryAlter.
 */

namespace Drupal\theme_system_sandbox\Theme;

use Drupal\Core\Theme\ThemeManagerInterface;

class RegistryAlter {

  /**
   * The contents of theme registry.
   *
   * @var array
   */
  protected $themeRegistry;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs ThemeRegistryAlter object.
   *
   * @param array $theme_registry
   *   The contents of theme registry.
   * @param \Drupal\Core\Theme\ThemeManagerInterface
   *   The theme manager.
   */
  public function __construct(array &$theme_registry, ThemeManagerInterface $theme_manager) {
    $this->themeRegistry = &$theme_registry;
    $this->themeManager = $theme_manager;
  }

  /**
   * Helper function to create initialize ThemeRegistryAlter.
   *
   * @param array $theme_registry
   *   The contents of theme registry.
   *
   * @return static
   */
  public static function create(array &$theme_registry) {
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    $container = \Drupal::getContainer();
    $theme_manager = $container->get('theme.manager');
    return new static($theme_registry, $theme_manager);
  }

  /**
   * Runs the alterations.
   */
  public function alter() {
    /** @var \Drupal\theme_system_sandbox\Theme\ActiveTheme $active_theme */
    $active_theme = $this->themeManager->getActiveTheme();

    if ($active_theme instanceof ActiveTheme) {
      foreach ($active_theme->getComponentOverrides() as $key => $component_override) {
        if (!isset($this->themeRegistry[$key])) {
          continue;
        }

        if (!empty($component_override['template'])) {
          $this->themeRegistry[$key]['template'] = $component_override['template'];
          $this->themeRegistry[$key]['path'] = $active_theme->getPath() . '/templates';
        }
      }
    }
  }

}
