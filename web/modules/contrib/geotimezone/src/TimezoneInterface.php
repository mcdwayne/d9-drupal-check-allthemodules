<?php

/**
 * @file
 * Contains \Drupal\geotimezone\TimezoneInterface.
 */

namespace Drupal\geotimezone;

/**
 * Provides an interface for different means to get time zone.
 *
 * @package Drupal\geotimezone
 */
interface TimezoneInterface {
  /**
   * Get the time zone identifier.
   *
   * @return string
   *   Time zone identifier. NULL if not found.
   */
  public function getIdentifier();

  /**
   * Get the time zone offset.
   *
   * @return string
   *   Time zone offset. NULL if not found.
   */
  public function getOffset();
}
