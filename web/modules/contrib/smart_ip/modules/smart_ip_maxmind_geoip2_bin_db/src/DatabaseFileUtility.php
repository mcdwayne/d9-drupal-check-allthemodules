<?php

/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_bin_db\DatabaseFileUtility.
 */

namespace Drupal\smart_ip_maxmind_geoip2_bin_db;

use Drupal\smart_ip_maxmind_geoip2_bin_db\EventSubscriber\SmartIpEventSubscriber;
use Drupal\smart_ip_maxmind_geoip2_bin_db\MaxmindGeoip2BinDb;
use Drupal\smart_ip\DatabaseFileUtilityBase;
use \Drupal\Component\Utility\UrlHelper;

/**
 * Utility methods class wrapper.
 *
 * @package Drupal\smart_ip_maxmind_geoip2_bin_db
 */
class DatabaseFileUtility extends DatabaseFileUtilityBase {

  /**
   * Get MaxMind GeoIP2 binary database filename.
   *
   * @param string $version
   * @param string $edition
   * @param bool $withExt
   * @return string
   */
  public static function getFilename($version = MaxmindGeoip2BinDb::LITE_VERSION, $edition = MaxmindGeoip2BinDb::CITY_EDITION, $withExt = TRUE) {
    if ($version == MaxmindGeoip2BinDb::LINCENSED_VERSION && $edition == MaxmindGeoip2BinDb::COUNTRY_EDITION) {
      $file = MaxmindGeoip2BinDb::FILENAME_LINCENSED_COUNTRY;
    }
    elseif ($version == MaxmindGeoip2BinDb::LINCENSED_VERSION && $edition == MaxmindGeoip2BinDb::CITY_EDITION) {
      $file = MaxmindGeoip2BinDb::FILENAME_LINCENSED_CITY;
    }
    elseif ($version == MaxmindGeoip2BinDb::LITE_VERSION && $edition == MaxmindGeoip2BinDb::COUNTRY_EDITION) {
      $file = MaxmindGeoip2BinDb::FILENAME_LITE_COUNTRY;
    }
    else {
      $file = MaxmindGeoip2BinDb::FILENAME_LITE_CITY;
    }
    if ($withExt) {
      return $file . MaxmindGeoip2BinDb::FILE_EXTENSION;
    }
    return $file;
  }

  /**
   * Download MaxMind GeoIP2 binary database file and extract it.
   * Only perform this action when the database is out of date or under specific
   * direction.
   */
  public static function downloadDatabaseFile() {
    $config     = \Drupal::config(SmartIpEventSubscriber::configName());
    $version    = $config->get('version');
    $edition    = $config->get('edition');
    $sourceId   = SmartIpEventSubscriber::sourceId();
    $file       = self::getFilename($version, $edition);
    $url        = '';
    if ($version == MaxmindGeoip2BinDb::LINCENSED_VERSION) {
      $url = MaxmindGeoip2BinDb::LINCENSED_DL_URL;
      $url .= '?' . UrlHelper::buildQuery([
        'license_key' => $config->get('license_key'),
        'edition_id'  => self::getFilename($version, $edition, FALSE),
        'suffix' => 'tar.gz',
      ]);
    }
    elseif ($version == MaxmindGeoip2BinDb::LITE_VERSION) {
      $url = MaxmindGeoip2BinDb::LITE_DL_URL;
      $url .= "/$file.gz";
    }
    if (parent::requestDatabaseFile($url, $file, $sourceId)) {
      \Drupal::state()->set('smart_ip_maxmind_geoip2_bin_db.last_update_time', \Drupal::time()->getRequestTime());
    }
  }

}
