<?php

namespace Drupal\svg_maps\Plugin\Field\FieldFormatter;

/**
 * Defines an interface for Svg maps formatter.
 */
interface SvgMapsFormatterInterface {

  /**
   * Returns if it's global or detail formatter.
   *
   * @return bool
   *   Return if formatter is global or not.
   */
  public static function isGlobal();

}
