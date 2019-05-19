<?php

namespace Drupal\device_geolocation\Plugin\Block;

use Drupal\Core\Block\BlockBase;


/**
 * Defines Device Geolocation block plugins.
 *
 * @Block(
 *   id = "visitor_geolocation",
 *   admin_label = @Translation("Visitor's geolocation"),
 * )
 */
class VisitorGeolocation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\smart_ip\SmartIpLocation $location */
    $location = \Drupal::service('smart_ip.smart_ip_location');
    $data = $location->getData(FALSE);
    return [
      '#theme'    => 'device_geolocation_visitor_info',
      '#location' => $data,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // No caching.
    $max_age = 0;
    return $max_age;
  }

}
