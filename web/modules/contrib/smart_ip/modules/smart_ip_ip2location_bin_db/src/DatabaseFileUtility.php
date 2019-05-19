<?php

/**
 * @file
 * Contains \Drupal\smart_ip_ip2location_bin_db\DatabaseFileUtility.
 */

namespace Drupal\smart_ip_ip2location_bin_db;

use Drupal\smart_ip_ip2location_bin_db\EventSubscriber\SmartIpEventSubscriber;
use Drupal\smart_ip_ip2location_bin_db\Ip2locationBinDb;
use Drupal\smart_ip\DatabaseFileUtilityBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Unicode;

/**
 * Utility methods class wrapper.
 *
 * @package Drupal\smart_ip_ip2location_bin_db
 */
class DatabaseFileUtility extends DatabaseFileUtilityBase {

  /**
   * Get IP2Location binary database filename.
   *
   * @param string $version
   *   Type of subscription: licensed or lite version.
   * @param string $edition
   *   IP2Location product code.
   * @param string $ipVersion
   *   IP version: IPv4 or IPv6.
   * @return string
   *   Returns the IP2Location binary database filename.
   */
  public static function getFilename($version = Ip2locationBinDb::LITE_VERSION, $edition = Ip2locationBinDb::DEFAULT_EDITION, $ipVersion = Ip2locationBinDb::IPV4_VERSION) {
    if ($version == Ip2locationBinDb::LINCENSED_VERSION) {
      $productName = Ip2locationBinDb::products($version, $edition);
      if ($ipVersion == Ip2locationBinDb::IPV6_VERSION) {
        $filename = 'IPV6-' . Unicode::strtoupper($productName) . '.BIN';
      }
      else {
        $filename = 'IP-' . Unicode::strtoupper($productName) . '.BIN';
      }
    }
    else {
      if ($ipVersion == Ip2locationBinDb::IPV6_VERSION) {
        $filename = "IP2LOCATION-LITE-$edition.IPV6.BIN";
      }
      else {
        $filename = "IP2LOCATION-LITE-$edition.BIN";
      }
    }
    return $filename;
  }

  /**
   * Download IP2Location binary database file and extract it.
   * Only perform this action when the database is out of date or under specific
   * direction.
   * @param int $ipVersion
   *   The IP address version: 4 or 6.
   */
  public static function downloadDatabaseFile($ipVersion) {
    $config    = \Drupal::config(SmartIpEventSubscriber::configName());
    $version   = $config->get('version');
    $edition   = $config->get('edition');
    $sourceId  = SmartIpEventSubscriber::sourceId();
    $file      = self::getFilename($version, $edition, $ipVersion);
    $urlIpv4   = '';
    $urlIpv6   = '';
    $currentIp = \Drupal::state()->get('smart_ip_ip2location_bin_db.current_ip_version_queue');
    if ($version == Ip2locationBinDb::LINCENSED_VERSION) {
      $token    = $config->get('token');
      $urlIpv4  = Ip2locationBinDb::LINCENSED_DL_URL;
      $urlIpv4 .= '?' . UrlHelper::buildQuery([
          'token' => $token,
          'file'  => $edition . 'BIN',
        ]);
      $urlIpv6  = Ip2locationBinDb::LINCENSED_DL_URL;
      $urlIpv6 .= '?' . UrlHelper::buildQuery([
          'token' => $token,
          'file'  => $edition . 'BINIPV6',
        ]);
    }
    elseif ($version == Ip2locationBinDb::LITE_VERSION) {
      $urlIpv4  = Ip2locationBinDb::LITE_DL_URL;
      $urlIpv4 .= '?' . UrlHelper::buildQuery([
          'db'      => $edition,
          'type'    => 'bin',
          'version' => Ip2locationBinDb::IPV4_VERSION,
        ]);
      $urlIpv6  = Ip2locationBinDb::LITE_DL_URL;
      $urlIpv6 .= '?' . UrlHelper::buildQuery([
          'db'      => $edition,
          'type'    => 'bin',
          'version' => Ip2locationBinDb::IPV6_VERSION,
        ]);
      // TODO:
      // The IP2Location lite account needs to be logged in first to be able
      // to download the bin files.
      return;
    }

    if ($currentIp == Ip2locationBinDb::IPV4_VERSION && parent::requestDatabaseFile($urlIpv4, $file, $sourceId)) {
      // Next, update IPv6 IP2Location binary database.
      \Drupal::state()->set('smart_ip_ip2location_bin_db.current_ip_version_queue', Ip2locationBinDb::IPV6_VERSION);
    }
    elseif ($currentIp == Ip2locationBinDb::IPV6_VERSION && parent::requestDatabaseFile($urlIpv6, $file, $sourceId)) {
      // All IPv4 and IPv6 IP2Location binary database files are already updated.
      \Drupal::state()->set('smart_ip_ip2location_bin_db.last_update_time', \Drupal::time()->getRequestTime());
      \Drupal::state()->set('smart_ip_ip2location_bin_db.current_ip_version_queue', NULL);
    }
  }

}
