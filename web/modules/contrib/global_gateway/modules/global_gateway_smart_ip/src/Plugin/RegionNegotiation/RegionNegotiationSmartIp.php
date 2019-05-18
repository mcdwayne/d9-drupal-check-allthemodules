<?php

namespace Drupal\global_gateway_smart_ip\Plugin\RegionNegotiation;

use Drupal\global_gateway\RegionNegotiationTypeBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the user preferences.
 *
 * @RegionNegotiation(
 *   id = "smart_ip",
 *   weight = -5,
 *   name = @Translation("Smart IP"),
 *   description = @Translation("Detect region code using Smart IP module.")
 * )
 */
class RegionNegotiationSmartIp extends RegionNegotiationTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getRegionCode(Request $request = NULL) {
    $region_code = FALSE;
    $ip = $request->getClientIp();

    $uid = \Drupal::currentUser()->id();

    if ($uid) {
      $user_data = \Drupal::service('user.data')->get('smart_ip', $uid);

      if (!empty($user_data['geoip_location']['location']['countryCode'])) {
        $region_code = $user_data['geoip_location']['location']['countryCode'];
      }
    }
    if (empty($region_code) || !empty($ip)) {
      $region_code = @\Drupal\smart_ip\SmartIp::query($ip)['countryCode'];
    }
    return $region_code;
  }

}
