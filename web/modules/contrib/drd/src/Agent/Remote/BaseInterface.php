<?php

namespace Drupal\drd\Agent\Remote;

/**
 * Interface for remote classes.
 */
interface BaseInterface {

  /**
   * Load all required classes of this namespace.
   *
   * @param int $version
   *   Main Drupal core version, between and including 6 to 8.
   */
  public static function loadClasses($version);

}
