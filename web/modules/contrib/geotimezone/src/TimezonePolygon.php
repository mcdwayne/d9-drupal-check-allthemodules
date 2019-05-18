<?php

/**
 * @file
 * Contains \Drupal\geotimezone\TimezonePolygon.
 */

namespace Drupal\geotimezone;

/**
 * Computes if given coordinates are inside the time zone polygon.
 *
 * @package Drupal\geotimezone
 */
class TimezonePolygon {
  /**
   * @var array $points
   */
  private $points = [];

  /**
   * TimezonePolygon constructor.
   */
  public function __construct() {
    $this->points = func_get_args();
  }

  /**
   * Determine if points are inside the polygon.
   *
   * @param float $y
   * @param float $x
   *
   * @return bool
   */
  public function isInside($y, $x) {
    $numPoints = count($this->points);
    $jY = $this->points[$numPoints - 2];
    $jX = $this->points[$numPoints - 1];
    $inside = FALSE;
    for ($i = 0; $i < $numPoints;) {
      $iY = $this->points[$i++];
      $iX = $this->points[$i++];
      if ((($iY > $y) != ($jY > $y)) && ($x < ($jX - $iX) * ($y - $iY) / ($jY - $iY) + $iX - 0.0001)) {
        $inside = !$inside;
      }
      $jX = $iX;
      $jY = $iY;
    }
    return $inside;
  }
}
