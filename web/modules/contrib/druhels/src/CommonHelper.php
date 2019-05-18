<?php

namespace Drupal\druhels;

class CommonHelper {

  /**
   * Return days count between two dates.
   *
   * @param string|integer $date_start
   * @param string|integer $date_end
   * @return integer
   */
  public static function getDaysBetweenDates($date_start, $date_end) {
    $date_start_object = new \DateTime(is_int($date_start) ? "@$date_start" : $date_start);
    $date_end_object = new \DateTime(is_int($date_end) ? "@$date_end" : $date_end);

    return $date_end_object->diff($date_start_object)->format('%a');
  }

  /**
   * Return TRUE if $date is between $date_start and $date_end.
   *
   * @param string|integer $date
   * @param string|integer $date_start
   * @param string|integer $date_end
   * @return boolean
   */
  public static function checkDateInDaterange($date, $date_start, $date_end) {
    if (!is_int($date)) {
      $date = strtotime($date);
    }
    if (!is_int($date_start)) {
      $date_start = strtotime($date_start);
    }
    if (!is_int($date_end)) {
      $date_end = strtotime($date_end);
    }

    return ($date >= $date_start && $date <= $date_end);
  }

}
