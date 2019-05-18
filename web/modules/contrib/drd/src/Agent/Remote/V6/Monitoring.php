<?php

namespace Drupal\drd\Agent\Remote\V6;

/**
 * Implements the Monitoring class.
 */
class Monitoring {

  /**
   * Placeholder to return an empty array as monitoring is not available on D6.
   *
   * @return array
   *   Empty array.
   */
  public static function collect() {
    return array();
  }

}
