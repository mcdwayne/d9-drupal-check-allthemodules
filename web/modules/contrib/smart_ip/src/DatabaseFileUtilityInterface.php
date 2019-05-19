<?php

/**
 * @file
 * Contains \Drupal\smart_ip\DatabaseFileUtilityInterface.
 */

namespace Drupal\smart_ip;

/**
 * Provides an interface for Smart IP's data source modules for its database
 * file.
 *
 * @package Drupal\smart_ip
 */
interface DatabaseFileUtilityInterface {

  /**
   * Get Smart IP's data source module's database filename.
   *
   * @return string
   *   Smart IP's data source module's database filename.
   */
  public static function getFilename();

  /**
   * Get Smart IP's data source module's database file's path.
   *
   * @param bool $autoUpdate
   *   Auto update flag.
   * @param string $customPath
   *   Smart IP's data source module's database file's user defined custom path.
   * @return string
   *   Smart IP's data source module's database file's path.
   */
  public static function getPath($autoUpdate, $customPath);

  /**
   * Checks if Smart IP's data source module's database file needs update.
   *
   * @param int $lastUpdateTime
   *   Smart IP's data source module's database file last update time.
   * @param bool $autoUpdate
   *   Auto update flag.
   * @param int $frequency
   *   Auto update frequency: weekly or monthly.
   * @return bool
   *   TRUE if Smart IP's data source module's database file needs update and
   *   FALSE if not.
   */
  public static function needsUpdate($lastUpdateTime, $autoUpdate, $frequency);

}
