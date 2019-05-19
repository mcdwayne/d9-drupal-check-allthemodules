<?php

namespace Drupal\widget_api;

/**
 * Class Widget.
 */
class Widget {

  /**
   * Returns the plugin manager for the Widget plugin type.
   *
   * @return \Drupal\widget_api\Plugin\Widget\WidgetPluginManagerInterface
   *   Widget manager.
   */
  public static function widgetPluginManager() {
    return \Drupal::service('plugin.manager.widget');
  }

}
