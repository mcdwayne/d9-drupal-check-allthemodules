<?php

namespace Drupal\slick_browser;

use Drupal\slick\SlickDefault;

/**
 * Defines shared plugin default settings for field widget and Views style.
 *
 * @todo: Consider overriding where applicable instead.
 */
class SlickBrowserDefault extends SlickDefault {

  /**
   * Overrides parent::getConstantBreakpoints().
   */
  public static function getConstantBreakpoints() {
    return ['xs', 'md', 'lg'];
  }

  /**
   * Returns the selection entity display plugin settings.
   */
  public static function baseFieldWidgetDisplaySettings() {
    return [
      '_context'           => 'widget',
      'image_style'        => 'slick_browser_thumbnail',
      'selection_position' => 'over-bottom',
      'view_mode'          => 'slick_browser',
    ];
  }

  /**
   * Returns the views style plugin settings.
   */
  public static function viewsSettings() {
    return [
      'grid'          => 0,
      'grid_header'   => '',
      'grid_medium'   => 0,
      'grid_small'    => 0,
      'visible_items' => 0,
      'optionset'     => 'default',
      'override'      => FALSE,
      'overridables'  => [],
      'skin'          => '',
      'vanilla'       => TRUE,
    ];
  }

  /**
   * Returns the form mode widget plugin settings.
   */
  public static function widgetSettings() {
    return [
      'box_style'           => '',
      'breakpoints'         => [],
      'image_style'         => 'slick_browser_preview',
      'optionset'           => '',
      'optionset_thumbnail' => '',
      'media_switch'        => 'media',
      'sizes'               => '',
      'skin_thumbnail'      => '',
      'style'               => '',
      'thumbnail_effect'    => '',
      'thumbnail_position'  => '',
      'thumbnail_style'     => 'slick_browser_thumbnail',
    ] + self::baseFieldWidgetDisplaySettings() + self::viewsSettings();
  }

}
