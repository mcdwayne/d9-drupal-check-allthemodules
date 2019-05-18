<?php

namespace Drupal\clarion_date;

/**
 * Provide functions for Clarion standard dates
 */
 class ClarionDate {
  /**
   * Convert a clarion date to a unix date.
   * 
   * @param int $date
   *   Clarion standard date.
   */
  public function fromClarion($date) {
    return ((($date - 61730) * 86400) + 18000);
  }

  /**
   * Convert a unix date to a clarion date.
   * 
   * @param int $date
   *   Unix date.
   */
  public function toClarion($date) {
    return ((($date - 18000) / 86400) + 61730);
  }

  /**
   * Change a formatted date to a timestamp.
   */
  public function formattedDateToTimestamp($format, $date) {
    $dateobj = date_create_from_format($format, $date);
    return intval(date_format($dateobj, 'U'));
  }

 }
