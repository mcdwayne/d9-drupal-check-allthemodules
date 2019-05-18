<?php

namespace Drupal\gridstack_field;


class GridstackFieldHelper {

  /**
   * Return array with keys of options for gridstack plugin separated by type.
   *
   * @param string $type
   *   Determine which type of options should be returned.
   *
   * @return array
   *   An array with options keys.
   */
  public static function getOptions($type) {
    $options = array();

    switch ($type) {
      case 'bool':
        $options = array(
          'animate',
          'alwaysShowResizeHandle',
          'auto',
          'disableDrag',
          'disableResize',
          'float',
        );
        break;

      case 'int':
        $options = array(
          'height',
          'width',
          'cellHeight',
          'minWidth',
          'rtl',
          'verticalMargin',
        );
        break;

      default:
        break;
    }

    return $options;
  }

  /**
   * Get enables content types displays.
   *
   * @param $type
   *
   * @return array
   */
  public static function getDisplays($type) {
    $view_modes = \Drupal::entityManager()->getViewModes('node');
    $view_mode_settings = \Drupal::entityManager()->getViewModeOptionsByBundle('node', $type);
    $displays = array();
    foreach ($view_modes as $view_mode_name => $view_mode_info) {
      if (isset($view_mode_settings[$view_mode_name])) {
        $displays[$view_mode_name] = $view_mode_info['label'];
      }
    }
    return $displays;
  }
}
