<?php

/**
 * @file
 * Contains \Drupal\smart_ip_ipinfodb_web_service\WebServiceUtility.
 */

namespace Drupal\smart_ip_ipinfodb_web_service;

use Drupal\smart_ip_ipinfodb_web_service\EventSubscriber\SmartIpEventSubscriber;
use Drupal\smart_ip_ipinfodb_web_service\IpinfodbWebService;
use Drupal\smart_ip\WebServiceUtilityBase;
use Drupal\Component\Serialization\Json;

/**
 * Utility methods class wrapper.
 *
 * @package Drupal\smart_ip_ipinfodb_web_service
 */
class WebServiceUtility extends WebServiceUtilityBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrl($ipAddress = NULL) {
    if (!empty($ipAddress)) {
      $config  = \Drupal::config(SmartIpEventSubscriber::configName());
      $apiKey  = $config->get('api_key');
      $version = $config->get('version');
      if ($version == 2) {
        return IpinfodbWebService::V2_URL . "?key=$apiKey&ip=$ipAddress&output=json&timezone=false";
      }
      elseif ($version == 3) {
        return IpinfodbWebService::V3_URL . "?key=$apiKey&ip=$ipAddress&format=json";
      }
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
