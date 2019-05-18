<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Gateways Controller.
 */
class OnpbxGateways extends ControllerBase {

  /**
   * Replace gateway name.
   */
  public static function gatewayName($gateway) {
    $data = &drupal_static("OnpbxGateways::userName($gateway)");
    if (!isset($data)) {
      $gateways_config = \Drupal::config('onlinepbx.settings')->get('gateways');
      $gateways = Yaml::parse($gateways_config);
      $data = $gateway;
      if (isset($gateways[$gateway])) {
        $data = $gateways[$gateway];
      }
    }
    return $data;
  }

}
