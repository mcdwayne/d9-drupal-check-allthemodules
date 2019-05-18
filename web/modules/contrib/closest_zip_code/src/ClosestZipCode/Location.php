<?php

namespace Drupal\closest_zip_code\ClosestZipCode;

use Drupal\closest_zip_code\traits\CommonUtilities;

/**
 * Represents a location.
 */
class Location {

  const MILES_PER_KM = 0.62137119;

  use CommonUtilities;

  /**
   * Contructor.
   *
   * @param string $zip
   *   A zip code.
   *
   * @throws Exception
   */
  public function __construct(string $zip) {
    $this->zip = $zip;
  }

  /**
   * Store the latitude and longitude internally.
   *
   * @throws Exception
   */
  protected function calcLatLon() {
    $latlon = $this->dataStore()->latLon($this->zip);
    $this->lat = $latlon['lat'];
    $this->lon = $latlon['lon'];
  }

  /**
   * Get the kms from another location.
   *
   * @param Location $start
   *   Another location.
   *
   * @return float
   *   Kms to that location.
   *
   * @throws \Exception
   */
  public function km(Location $start) : float {
    return $this->vincentyGreatCircleDistance($start->lat(), $start->lon(), $this->lat(), $this->lon()) / 1000;
  }

  /**
   * Latitude of this location.
   *
   * @return float
   *   Latitude.
   *
   * @throws Exception
   */
  public function lat() : float {
    if (empty($this->lat)) {
      $this->calcLatLon();
    }
    return $this->lat;
  }

  /**
   * Longitude of this location.
   *
   * @return float
   *   Longitude.
   *
   * @throws Exception
   */
  public function lon() : float {
    if (empty($this->lon)) {
      $this->calcLatLon();
    }
    return $this->lon;
  }

  /**
   * Get the miles from another location.
   *
   * @param Location $start
   *   Another location.
   *
   * @return float
   *   Miles to that location.
   *
   * @throws Exception
   */
  public function miles(Location $start) : float {
    return $this->km($start) * self::MILES_PER_KM;
  }

  /**
   * Calculates the great-circle distance between two points.
   *
   * Uses the Vincenty formula, see
   * https://stackoverflow.com/a/10054282/1207752.
   *
   * @param float $latitudeFrom
   *   Latitude of start point in [deg decimal].
   * @param float $longitudeFrom
   *   Longitude of start point in [deg decimal].
   * @param float $latitudeTo
   *   Latitude of target point in [deg decimal].
   * @param float $longitudeTo
   *   Longitude of target point in [deg decimal].
   * @param float $earthRadius
   *   Mean earth radius in [m].
   *
   * @return float
   *   Distance between points in [m] (same as earthRadius).
   */
  protected static function vincentyGreatCircleDistance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, float $earthRadius = 6371000) : float {
    // Convert from degrees to radians.
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $lonDelta = $lonTo - $lonFrom;
    $a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
    $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

    $angle = atan2(sqrt($a), $b);
    return $angle * $earthRadius;
  }

}
