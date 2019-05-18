<?php

/**
 * @file
 * Contains \Drupal\ip2location\AddHTTPHeaders.
 */

namespace Drupal\ip2location;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides AddHTTPHeaders.
 */
class AddHTTPHeaders implements EventSubscriberInterface {

  /**
   * Sets extra HTTP headers.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    $config = \Drupal::config('ip2location.settings');

    $database_path = $config->get('database_path');
    $cache_mode = $config->get('cache_mode');

    if (!is_file($database_path)) {
      return;
    }

    $ip = \Drupal::request()->getClientIp();

    if (isset($_SERVER['DEV_MODE'])) {
	  $ip = '8.8.8.8';
    }

    $session = \Drupal::service('session')->get('ip2location_' . $ip);

    if (is_null($json = json_decode($session))) {
      module_load_include('inc', 'ip2location', 'src/IP2Location');

      switch ($cache_mode) {
        case 'memory_cache':
          $ip2location = new \IP2Location\Database($database_path,  \IP2Location\Database::MEMORY_CACHE);
          break;

        case 'shared_memory':
          $ip2location = new \IP2Location\Database($database_path,  \IP2Location\Database::SHARED_MEMORY);
        break;

        default:
          $ip2location = new \IP2Location\Database($database_path,  \IP2Location\Database::FILE_IO);
      }

      $records = $ip2location->lookup($ip,  \IP2Location\Database::ALL);

      $raw = json_encode(array(
      'ip_address' => $ip,
      'country_code' => $records['countryCode'],
      'country_name' => $records['countryName'],
      'region_name' => $records['regionName'],
      'city_name' => $records['cityName'],
      'latitude' => $records['latitude'],
      'longitude' => $records['longitude'],
      'isp' => $records['isp'],
      'domain_name' => $records['domainName'],
      'zip_code' => $records['zipCode'],
      'time_zone' => $records['timeZone'],
      'net_speed' => $records['netSpeed'],
      'idd_code' => $records['iddCode'],
      'area_code' => $records['areaCode'],
      'weather_station_code' => $records['weatherStationCode'],
      'weather_station_name' => $records['weatherStationName'],
      'mcc' => $records['mcc'],
      'mnc' => $records['mnc'],
      'mobile_carrier_name' => $records['mobileCarrierName'],
      'elevation' => $records['elevation'],
      'usage_type' => $records['usageType'],
      ));

      $json = json_decode($raw);

      \Drupal::service('session')->set('ip2location_' . $ip, $raw);
    }

    $fields = array(
	  'ip_address' => 'X-IP-Address',
      'country_code' => 'X-Country-Code',
      'country_name' => 'X-Country-Name',
      'region_name' => 'X-Region-Name',
      'city_name' => 'X-City-Name',
      'latitude' => 'X-Latitude',
      'longitude' => 'X-Longitude',
      'isp' => 'X-ISP',
      'domain_name' => 'X-Domain-Name',
      'zip_code' => 'X-ZIP-Code',
      'time_zone' => 'X-Time-Zone',
      'net_speed' => 'X-Net-Speed',
      'idd_code' => 'X-IDD-Code',
      'area_code' => 'X-Area-Code',
      'weather_station_code' => 'X-Weather-Station-Code',
      'weather_station_name' => 'X-Weather-Station-Name',
      'mcc' => 'X-MCC',
      'mnc' => 'X-MNC',
      'mobile_carrier_name' => 'X-Mobile-Carrier-Name',
      'elevation' => 'X-Elevation',
      'usage_type' => 'X-Usage-Type',
    );

    foreach ($fields as $key => $value) {
      if (isset($json->$key) && $json->$key != '-' && !preg_match('/unavailable/', $json->$key)) {
        $response->headers->set($value, $json->$key);
	  }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
