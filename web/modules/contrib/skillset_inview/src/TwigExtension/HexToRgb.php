<?php

namespace Drupal\skillset_inview\TwigExtension;

/**
 * A Twig extension (filter) converts hex color to rgb.
 */
class HexToRGB extends \Twig_Extension {

  /**
   * An empty Constructor.  Parview warning.
   */
  public function __construct() {}

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('hexToRGB', [$this, 'hexToRGB'], ['is_safe' => ['html']]),
      new \Twig_SimpleFilter('rangeToPercent', [$this, 'rangeToPercent'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'skillset_inview.twig.color_convert';
  }

  /**
   * Convert 3 or 6 char hex color to comma separated RGB colors.
   */
  public static function hexToRgb($hex = 000000) {
    $r = $g = $b = 0;
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
      $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
      $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    }
    else {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }

    $rgb = array($r, $g, $b);

    return implode(",", $rgb);
  }

  /**
   * Convert range (0 to 100) to a percent with 2 decimals.
   */
  public static function rangeToPercent($range = 0) {
    return round(($range / 100), 2);
  }

}
