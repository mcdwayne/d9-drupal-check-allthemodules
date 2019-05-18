<?php

namespace Drupal\bridtv;

/**
 * Holds all methods regards (un)serialization.
 */
abstract class BridSerialization {

  /**
   * Decodes retrieved JSON data.
   *
   * @param string $encoded
   *   The JSON-encoded data.
   *
   * @return array
   *   The decoded data as associative array.
   */
  public static function decode($encoded) {
    return json_decode($encoded, TRUE);
  }

}
