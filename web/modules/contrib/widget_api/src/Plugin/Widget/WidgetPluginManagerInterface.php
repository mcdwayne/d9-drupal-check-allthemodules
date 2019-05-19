<?php

namespace Drupal\widget_api\Plugin\Widget;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of widget plugins.
 */
interface WidgetPluginManagerInterface extends PluginManagerInterface {

  /**
   * Get all available widgets as an options array.
   *
   * If group_by_category option/parameter passed group the options by
   * category.
   *
   * @param array $params
   *   (optional) An associative array with the following keys:
   *   - group_by_category: (bool) If set to TRUE, return an array of arrays
   *   grouped by the category name; otherwise, return a single-level
   *   associative array.
   *
   * @return array
   *   Widget options, as array.
   */
  public function getWidgetOptions(array $params = []);

  /**
   * Registers the theme implementations.
   */
  public function getThemeImplementations();

  /**
   * Alters the theme implementations.
   */
  public function alterThemeImplementations(array &$theme_registry);

}
