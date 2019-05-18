<?php
/**
 * @file
 * Contains \Drupal\draggable_blocks\Plugin\Draggable\DraggablePluginManagerInterface.
 */

namespace Drupal\draggable_blocks\Plugin\Draggable;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of layout plugins.
 */
interface DraggablePluginManagerInterface extends PluginManagerInterface {

  /**
   * Get Regions.
   *
   * @return array
   *   An associative array of all regions with block container jQuery selector.
   *
   */
  public function getRegions();

}
