<?php
/**
 * @file
 * Contains DraggableapiManager.
 */

namespace Drupal\draggable_blocks;

/**
 * Draggablesapi plugin manager.
 */
class Draggable {


  /**
   * Returns the plugin manager for the Layout plugin type.
   *
   * @return \Drupal\draggable_plugin\Plugin\Draggable\DraggablePluginManagerInterface
   *   Layout manager.
   */
  public static function draggablePluginManager() {
    return \Drupal::service('plugin.manager.draggable_blocks');
  }

  /**
   * Return all available layout as an options array.
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
   *   Layout options, as array.
   *
   * @deprecated
   *   Use \Drupal\draggable_blocks\Plugin\Draggable\DraggablePluginManagerInterface::getLayoutOptions().
   */
  public static function getDraggableOptions(array $params = []) {
    return static::draggableapiPluginManager()->getDraggableOptions($params);
  }

  /**
   * Return theme implementations for layouts that give only a template.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   *
   * @deprecated
   *   Use \Drupal\draggable_blocks\Plugin\Draggable\DraggablePluginManagerInterface::getThemeImplementations().
   */
  public static function getThemeImplementations() {
    return static::draggableapiPluginManager()->getThemeImplementations();
  }

}
