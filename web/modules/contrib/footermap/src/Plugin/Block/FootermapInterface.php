<?php
/**
 * @file
 *   Contains /Drupal/footermap/Plugin/Block/Footermap.
 */

namespace Drupal\footermap\Plugin\Block;

/**
 * Interface for Footermap clasess.
 */
interface FootermapInterface {

  /**
   * Build content for footer site map. The default implementation should call
   * buildMenu() recursively.
   *
   * This deprecates footermap_render().
   *
   * @return
   *   An associative array ready for render system.
   *
   * @todo The return value may change by the time Drupal 8 is released.
   *
   * @see Drupal\footermap\Plugin\Block\Footermap::buildMenu().
   */
  public function buildMap();

  /**
   * Recursively build footer site map. This method should modify the object
   * variable $mapref.
   *
   * This deprecates footermap_get_menu().
   *
   * @param [] &$tree
   *   A reference to the menu site tree for a particular menu.
   * @param [] &$mapref
   *   A reference to the current menu item's children or the root of the map.
   */
  public function buildMenu(&$tree, &$mapref);

}
