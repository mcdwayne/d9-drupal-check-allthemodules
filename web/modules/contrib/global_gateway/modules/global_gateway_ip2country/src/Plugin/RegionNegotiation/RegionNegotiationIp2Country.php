<?php

namespace Drupal\global_gateway_ip2country\Plugin\RegionNegotiation;

use Drupal\global_gateway\RegionNegotiationTypeBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the user preferences.
 *
 * @RegionNegotiation(
 *   id = "ip2country",
 *   weight = -5,
 *   name = @Translation("IP 2 Country"),
 *   description = @Translation("Detect region code using ip2country module.")
 * )
 */
class RegionNegotiationIp2Country extends RegionNegotiationTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getRegionCode(Request $request = NULL) {
    $region_code = FALSE;

    $uid = \Drupal::currentUser()->id();
    $ip = $request->getClientIp();

    if ($uid) {
      $region_code = \Drupal::service('user.data')->get('ip2country', $uid, 'country_iso_code_2');
    }
    if ((empty($region_code) || !empty($ip)) && function_exists('ip2country_get_country')) {
      $region_code = ip2country_get_country($ip);
    }
    return $region_code;
  }

}
