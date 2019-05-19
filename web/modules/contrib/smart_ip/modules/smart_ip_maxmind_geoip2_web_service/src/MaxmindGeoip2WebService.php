<?php
/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_web_service\MaxmindGeoip2WebService.
 */

namespace Drupal\smart_ip_maxmind_geoip2_web_service;


class MaxmindGeoip2WebService {

  /**
   * MaxMind GeoIP2 Precision web service base query URL.
   */
  const BASE_URL = 'geoip.maxmind.com/geoip/v2.1';

  /**
   * MaxMind GeoIP2 Precision web service Country service.
   */
  const COUNTRY_SERVICE = 'country';

  /**
   * MaxMind GeoIP2 Precision web service City service.
   */
  const CITY_SERVICE = 'city';

  /**
   * MaxMind GeoIP2 Precision web service Insights service.
   */
  const INSIGHTS_SERVICE = 'insights';

}
