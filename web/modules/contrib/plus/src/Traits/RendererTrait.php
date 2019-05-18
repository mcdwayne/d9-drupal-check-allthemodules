<?php

namespace Drupal\plus\Traits;

/**
 * Trait RendererTrait.
 */
trait RendererTrait {

  /**
   * The Renderer service.
   *
   * @var \Drupal\plus\Core\Render\Renderer
   */
  protected static $renderer;

  /**
   * Retrieves the Renderer service.
   *
   * @return \Drupal\plus\Core\Render\Renderer
   *   The Renderer service.
   */
  public static function getRenderer() {
    if (static::$renderer) {
      try {
        static::$renderer = \Drupal::service('renderer');
      }
      catch (\Exception $e) {
        // Intentionally left blank.
      }
    }
    return static::$renderer;
  }

}
