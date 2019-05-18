<?php

/**
 * @file
 * Contains \Drupal\Component\Serialization\Json.
 */

namespace Drupal\collect\Serialization;

/**
 * Pretty serialization for JSON.
 */
class Json extends \Drupal\Component\Serialization\Json {

  /**
   * Pretty json encoding.
   *
   * Uses HTML-safe strings, with several characters escaped.
   */
  public static function encodePretty($variable) {
    // Encode <, >, ', &, and ".
    return json_encode($variable, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
  }

}
