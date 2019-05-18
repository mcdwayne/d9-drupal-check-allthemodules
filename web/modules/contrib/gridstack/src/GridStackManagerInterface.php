<?php

namespace Drupal\gridstack;

use Drupal\blazy\BlazyManagerInterface;

/**
 * Defines re-usable services and functions for gridstack plugins.
 */
interface GridStackManagerInterface extends BlazyManagerInterface {

  /**
   * Returns a cacheable renderable array of a single gridstack instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of gridstack contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS options.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a gridstack instance, or empty array.
   */
  public function build(array $build = []);

  /**
   * Modifies GridStack boxes to support nested grids for Bootstrap/ Foundation.
   *
   * The nested grids require extra tools like DS, Panelizer, or Widget, to
   * arrange them into their relevant container, e.g.: DS region, Widget block.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of gridstack contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS options.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   * @param array $regions
   *   The available region attributes normally provided by Panels for admin.
   *
   * @return array
   *   The renderable array of a GridStack instance, or empty array.
   */
  public function buildItems(array $build, array $regions = []);

  /**
   * Provides dynamic JS or static Bootstrap/ Foundation grid attributes.
   *
   * Available attributes:
   *   - Base: x, y, width, height.
   *   - Extra: autoPosition, minWidth, maxWidth, minHeight, maxHeight, id.
   *
   * @param array $settings
   *   The settings being modified.
   * @param string $current
   *   The current box identifier: grids, or nested.
   *
   * @return array
   *   The array of attributes for each box, either main, or nested boxes.
   */
  public function boxAttributes(array &$settings, $current = 'grids');

  /**
   * Provides Layout Builder and Panels IPE attributes if available.
   *
   * GridStackLayout has no knowledge of IPE, and IPE expects region keys which
   * are not provided by GridStack, hence rebuild needed attributes.
   *
   * @param array $box
   *   The box being modified.
   * @param array $attributes
   *   The attributes being modified.
   * @param array $content_attributes
   *   The content attributes being modified.
   * @param array $settings
   *   The settings.
   * @param array $regions
   *   The region attributes provided by Layout Builder or Panels for admin.
   * @param string $rid
   *   The region ID.
   */
  public function adminAttributes(array &$box, array &$attributes, array &$content_attributes, array $settings, array $regions = [], $rid = NULL);

}
