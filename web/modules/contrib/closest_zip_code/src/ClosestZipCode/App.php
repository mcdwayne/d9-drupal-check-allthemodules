<?php

namespace Drupal\closest_zip_code\ClosestZipCode;

use Drupal\closest_zip_code\traits\Singleton;
use Drupal\closest_zip_code\traits\CommonUtilities;

/**
 * Represents the Closest Zip Code API.
 */
class App {

  use Singleton;
  use CommonUtilities;

  /**
   * Get the closest zip code to a zip code from list of zip codes.
   *
   * @param string $my_zip
   *   A zip code.
   * @param array $all_zips
   *   An array of zip codes.
   *
   * @return array
   *   An array with the following keys:
   *   * errors: array of strings.
   *   * zips: [99999 => [km => 123.5, miles => 234. 5, lat => 1, lon => 2],
   *     ...]. (All zips are ordered by distance.)
   *   * lat: latitude of my_zip.
   *   * lon: longitude of my_zip.
   *
   * @throws Exception
   */
  public function closestZipCode(string $my_zip, array $all_zips) : array {
    $return = [
      'errors' => [],
      'zip' => $my_zip,
    ];
    $start = $this->microtime(TRUE);
    try {
      $my_location = $this->location($my_zip);
      $return['lat'] = $my_location->lat();
      $return['lon'] = $my_location->lon();
      foreach ($all_zips as $zip) {
        try {
          $location = $this->location($zip);
          $zip_info['km'] = $location->km($my_location);
          $zip_info['miles'] = $location->miles($my_location);
          $zip_info['lat'] = $location->lat();
          $zip_info['lon'] = $location->lon();
        }
        catch (\Throwable $e) {
          $return['errors'][] = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
          continue;
        }
        $return['zips'][(string) $zip] = $zip_info;
      }
      $return['zips'] = $this->sortByDistance($return['zips']);
    }
    catch (\Throwable $t) {
      $return['errors'][] = $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine();
    }
    $return['duration-seconds'] = $this->microtime(TRUE) - $start;
    return $return;
  }

  /**
   * Transform a zip code to a location.
   *
   * @param string $zip
   *   A zip code.
   *
   * @return Location
   *   A location.
   *
   * @throws \Exception
   */
  public function location(string $zip) : Location {
    return new Location($zip);
  }

  /**
   * Sort locations by distance.
   *
   * @param array $locations
   *   Locations, each of which needs to have a km key that will be used
   *   for sorting, e.g. [123 => [km => 20], 234 => [km => 3]].
   *
   * @return array
   *   Array sorted by distance, e.g. [234 => [km => 3], 123 => [km => 20]].
   *
   * @throws Exception
   */
  public function sortByDistance(array $locations) : array {
    if (!uasort($locations, function ($a, $b) {
      if (!isset($a['km']) || !isset($b['km'])) {
        throw new \Exception('::sortByDistance(): all items in the array need to have a "km" key.');
      }
      if ($a['km'] == $b['km']) {
        return 0;
      }
      return ($a['km'] < $b['km']) ? -1 : 1;
    })) {
      throw new \Exception('uasort() returned FALSE.');
    }
    return $locations;
  }

}
