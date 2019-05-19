<?php
/**
 * @file
 * Contains \Drupal\smart_ip_ip2location_bin_db\Ip2locationBinDb.
 */

namespace Drupal\smart_ip_ip2location_bin_db;


class Ip2locationBinDb {

  /**
   * IP2Location licensed version.
   */
  const LINCENSED_VERSION = 'licensed';

  /**
   * IP2Location lite or free version.
   */
  const LITE_VERSION = 'lite';

  /**
   * IP2Location default edition.
   */
  const DEFAULT_EDITION = 'DB11';

  /**
   * IP2Location IPV4 version.
   */
  const IPV4_VERSION = 4;

  /**
   * IP2Location IPV6 version.
   */
  const IPV6_VERSION = 6;

  /**
   * IP2Location licensed version download URL.
   */
  const LINCENSED_DL_URL = 'https://www.ip2location.com/download';

  /**
   * IP2Location lite or free version download URL.
   */
  const LITE_DL_URL = 'https://lite.ip2location.com/download';

  /**
   * Standard lookup with no cache and directly reads from the database file.
   */
  const NO_CACHE = 'no_cache';

  /**
   * Cache the database into memory to accelerate lookup speed.
   */
  const MEMORY_CACHE = 'memory_cache';

  /**
   * Cache whole database into system memory and share among other scripts and
   * websites.
   */
  const SHARED_MEMORY = 'shared_memory';

  /**
   * IP2Location binary database file no issue error code.
   */
  const DB_NO_ERROR = 0;

  /**
   * IP2Location binary database file does not exist error code.
   */
  const DB_NOT_EXIST_ERROR = 1;

  /**
   * IP2Location binary database file is not valid or corrupted error code.
   */
  const DB_READ_ERROR = 2;

  /**
   * Loading IP2Location binary database file failed error code.
   */
  const DB_LOAD_ERROR = 3;

  /**
   * IP2Location product code/name look-up table.
   *
   * @param string $version
   *   Type of subscription: licensed or lite version.
   * @param string $edition
   *   IP2Location product ID.
   * @return mixed
   *   Returns the product name if product code is supplied and return all
   *   products list if no product code.
   */
  public static function products($version = self::LITE_VERSION, $edition = '') {
    if ($version == self::LINCENSED_VERSION) {
      $products = [
        'DB1'  => 'Country',
        'DB2'  => 'Country-ISP',
        'DB3'  => 'Country-Region-City',
        'DB4'  => 'Country-Region-City-ISP',
        'DB5'  => 'Country-Region-City-Latitude-Longitude',
        'DB6'  => 'Country-Region-City-Latitude-Longitude-ISP',
        'DB7'  => 'Country-Region-City-ISP-Domain',
        'DB8'  => 'Country-Region-City-Latitude-Longitude-ISP-Domain',
        'DB9'  => 'Country-Region-City-Latitude-Longitude-ZIPCode',
        'DB10' => 'Country-Region-City-Latitude-Longitude-ZIPCode-ISP-Domain',
        'DB11' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone',
        'DB12' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain',
        'DB13' => 'Country-Region-City-Latitude-Longitude-TimeZone-NetSpeed',
        'DB14' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain-NetSpeed',
        'DB15' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-AreaCode',
        'DB16' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain-NetSpeed-AreaCode',
        'DB17' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-NetSpeed-Weather',
        'DB18' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain-NetSpeed-AreaCode-Weather',
        'DB19' => 'Country-Region-City-Latitude-Longitude-ISP-Domain-Mobile',
        'DB20' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain-NetSpeed-AreaCode-Weather-Mobile',
        'DB21' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-AreaCode-Elevation',
        'DB22' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain-NetSpeed-AreaCode-Weather-Mobile-Elevation',
        'DB23' => 'Country-Region-City-Latitude-Longitude-ISP-Domain-Mobile-UsageType',
        'DB24' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone-ISP-Domain-NetSpeed-AreaCode-Weather-Mobile-Elevation-UsageType',
      ];
    }
    else {
      $products = [
        'DB1'  => 'Country',
        'DB3'  => 'Country-Region-City',
        'DB5'  => 'Country-Region-City-Latitude-Longitude',
        'DB9'  => 'Country-Region-City-Latitude-Longitude-ZIPCode',
        'DB11' => 'Country-Region-City-Latitude-Longitude-ZIPCode-TimeZone',
      ];
    }
    if (!empty($edition)) {
      return $products[$edition];
    }
    else {
      return $products;
    }
  }

}
