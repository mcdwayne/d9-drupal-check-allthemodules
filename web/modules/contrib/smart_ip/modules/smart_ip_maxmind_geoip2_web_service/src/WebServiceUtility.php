<?php

/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_web_service\WebServiceUtility.
 */

namespace Drupal\smart_ip_maxmind_geoip2_web_service;

use Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber\SmartIpEventSubscriber;
use Drupal\smart_ip_maxmind_geoip2_web_service\MaxmindGeoip2WebService;
use Drupal\smart_ip\WebServiceUtilityBase;
use Drupal\Component\Serialization\Json;

/**
 * Utility methods class wrapper.
 *
 * @see http://dev.maxmind.com/geoip/geoip2/web-services
 * @package Drupal\smart_ip_maxmind_geoip2_web_service
 */
class WebServiceUtility extends WebServiceUtilityBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrl($ipAddress = NULL) {
    if (!empty($ipAddress)) {
      $config = \Drupal::config(SmartIpEventSubscriber::configName());
      $userId = $config->get('user_id');
      $licenseKey  = $config->get('license_key');
      $serviceType = $config->get('service_type');
      return "https://$userId:$licenseKey@" . MaxmindGeoip2WebService::BASE_URL . "/$serviceType/$ipAddress";
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getGeolocation($ipAddress = NULL) {
    $url  = self::getUrl($ipAddress);
    $json = self::sendRequest($url);
    $data = Json::decode($json);
    return $data;
  }

}
