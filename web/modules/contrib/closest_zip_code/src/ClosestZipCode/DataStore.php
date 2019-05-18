<?php

namespace Drupal\closest_zip_code\ClosestZipCode;

use Drupal\closest_zip_code\traits\Singleton;
use Drupal\closest_zip_code\traits\CommonUtilities;

/**
 * Represents the data store.
 */
class DataStore {

  use Singleton;
  use CommonUtilities;

  const BOUNDS_HIGHER = 99999;
  const BOUNDS_LOWER = 500;
  const FUZZINESS = 50;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->preload();
  }

  /**
   * Given a zip code, get a latitude and longitude.
   *
   * @param string $zip
   *   A zip code.
   *
   * @return array
   *   An array with lat and lon keys.
   *
   * @throws Exception
   */
  public function latLon(string $zip) : array {
    $line = $this->line($zip);
    return [
      'lat' => $line[1],
      'lon' => $line[2],
    ];
  }

  /**
   * Get a line from the preloaded CSV file containing lat and lon for a zip.
   *
   * @param string $zip
   *   A zip code.
   *
   * @return array
   *   A line with 0 => zip, 1 => lat, 2 => lon.
   *
   * @throws Exception
   */
  public function line(string $zip) : array {
    $lines = $this->lines();
    $i = 0;
    do {
      if (!empty($lines[$this->zipOffset($zip, $i)])) {
        return $lines[$this->zipOffset($zip, $i)];
      }
      if (!empty($lines[$this->zipOffset($zip, -$i)])) {
        return $lines[$this->zipOffset($zip, -$i)];
      }
    } while (++$i <= self::FUZZINESS);
    throw new \Exception($this->t('zip @z not found with fuzziness @f', [
      '@z' => $this->zipOffset($zip, 0),
      '@f' => self::FUZZINESS,
    ]));
  }

  /**
   * Get all lines with all zip codes.
   *
   * @return array
   *   Array of all lines keyed by zip.
   */
  public function lines() : array {
    return $this->lines;
  }

  /**
   * Preload all zip codes, for speedier execution.
   *
   * Stores the results internally.
   *
   * @throws Exception
   */
  public function preload() {
    $this->lines = [];
    $handle = $this->fopen($this->drupalGetPath('module', 'closest_zip_code') . '/data/zipcodes.csv', 'r');
    while ($row = $this->fgetcsv($handle)) {
      $this->lines[(string) $row[0]] = $row;
    }
    $this->fclose($handle);
  }

  /**
   * Function.
   *
   * Description.
   *
   * @param string $zip
   *   What.
   * @param int $offset
   *   What.
   *
   * @return string
   *   A.
   *
   * @throws Exception
   */
  public function zipOffset(string $zip, int $offset) : string {
    if ($zip < self::BOUNDS_LOWER) {
      throw new \Exception('Zip lower than lower bounds.');
    }
    if ($zip > self::BOUNDS_HIGHER) {
      throw new \Exception('Zip higher than higher bounds.');
    }
    if (!is_numeric($zip)) {
      throw new \Exception('Zip must be numeric.');
    }

    return sprintf('%05d', $zip + $offset);
  }

}
