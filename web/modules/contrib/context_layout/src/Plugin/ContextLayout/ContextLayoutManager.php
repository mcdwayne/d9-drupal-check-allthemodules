<?php

namespace Drupal\context_layout\Plugin\ContextLayout;

use Drupal\Core\Layout\LayoutPluginManager;

/**
 * Provides an interface for the discovery and instantiation of context layouts.
 */
class ContextLayoutManager extends LayoutPluginManager {

  /**
   * Returns a Drupal\layout_plugin\Layout instance.
   *
   * @param string $layout
   *   Layout ID (machine name).
   * @param bool|false $fallback
   *   Whether to return a fallback layout if default doesn't exist.
   *
   * @return object
   *   Drupal\layout_plugin\Layout instance.
   */
  public function loadLayout($layout, $fallback = FALSE) {
    // We want to return the correct layout if 'default' is passed.
    if ('default' == $layout) {
      $layout = $this->createInstance($this->getDefaultLayout($fallback));
    }
    else {
      $layout = $this->createInstance($layout);
    }
    return $layout;
  }

  /**
   * Returns default Drupal\layout_plugin\Layout instance.
   *
   * @param bool|false $fallback
   *   Whether to return a fallback layout if default doesn't exist.
   *
   * @return string
   *   Layout ID (machine name).
   */
  public function getDefaultLayout($fallback = FALSE) {
    $layout = \Drupal::config('context_layout.settings')
      ->get('default_layout');
    if ($fallback && !$layout) {
      // Get the first available layout.
      $layout = array_keys(
        $this->getLayoutOptions()
      )[0];
    }
    return $layout;
  }

}
