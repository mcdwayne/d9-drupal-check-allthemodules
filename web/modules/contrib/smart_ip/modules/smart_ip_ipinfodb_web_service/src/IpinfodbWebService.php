<?php
/**
 * @file
 * Contains \Drupal\smart_ip_ipinfodb_web_service\IpinfodbWebService.
 */

namespace Drupal\smart_ip_ipinfodb_web_service;


class IpinfodbWebService {

  /**
   * IPInfoDB web service version 2 query URL.
   */
  const V2_URL = 'http://api.ipinfodb.com/v2/ip_query.php';

  /**
   * IPInfoDB web service version 3 query URL.
   */
  const V3_URL = 'http://api.ipinfodb.com/v3/ip-city';

}
