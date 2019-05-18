<?php

/**
 * @file
 * Contains \Drupal\ipgeobase\Plugin\Block\IPGeobaseBlock.
 * Block with IP information fetched from ipgeobase.ru database.
 */

namespace Drupal\ipgeobase\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a ipgeobase block.
 *
 * @Block(
 *   id = "ipgeobase_block",
 *   admin_label = @Translation("IPGeobase user location"),
 *   category = @Translation("Geolocation")
 * )
 */
class IPGeobaseBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ip = \Drupal::request()->getClientIp();
    $geolocation = ipgeobase_get_geolocation($ip);

    $not_found = ($geolocation === FALSE);
    $city = !empty($geolocation->city) ? $geolocation->city : '';
    $ip = !empty($geolocation->ip) ? $geolocation->ip : $ip;
    $latitude = !empty($geolocation->lat) ? $geolocation->lat : '';
    $longitude = !empty($geolocation->lng) ? $geolocation->lng : '';
    $region = !empty($geolocation->region) ? $geolocation->region : '';
    $district = !empty($geolocation->district) ? $geolocation->district : '';

    return array(
     'not_found' => $not_found,
     'city' => $city,
     'ip' => $ip,
     'latitude' => $latitude,
     'longitude' => $longitude,
     'region' => $region,
     'district' => $district,
   );
  }
}