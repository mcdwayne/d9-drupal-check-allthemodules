<?php

namespace Drupal\global_gateway\Plugin\RegionNegotiation;

use Drupal\global_gateway\RegionNegotiationTypeBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns site-wide default region code.
 *
 * @RegionNegotiation(
 *   id = "default",
 *   weight = -4,
 *   name = @Translation("Default region"),
 *   description = @Translation("Use default region code preference."),
 *   config_route_name = "global_gateway_ui.negotiation_default"
 * )
 */
class RegionNegotiationDefault extends RegionNegotiationTypeBase {

  protected $region_code;

  /**
   * {@inheritdoc}
   */
  public function getRegionCode(Request $request = NULL) {
    $default = \Drupal::config('system.date')->get('country.default');
    $code = $this->get('region_code');

    return $code ?: $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $config = parent::getConfiguration();
    $config['region_code'] = $this->get('region_code');
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled' => TRUE,
    ];
  }

}
