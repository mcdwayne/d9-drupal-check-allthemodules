<?php

namespace Drupal\svg_maps\Plugin\SvgMaps\Type;

use Drupal\svg_maps\SvgMapsTypeBase;

/**
 * Provides svg maps type plugin for fr departments.
 *
 * @SvgMapsType(
 *   id = "generic",
 *   label = @Translation("Generic"),
 *   description = @Translation("Generic")
 * )
 */
class Generic extends SvgMapsTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getGlobalTheme(){
    return 'svg_maps_generic';
  }

  /**
   * {@inheritdoc}
   */
  public function getDetailedTheme(){
    return 'svg_maps_generic_detail';
  }
}
