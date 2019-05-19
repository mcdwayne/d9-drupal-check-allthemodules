<?php

namespace Drupal\weatherstation\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Weatherstation block.
 *
 * @Block(
 *   id = "weather_block",
 *   admin_label = @Translation("Weather block")
 * )
 */
class WeatherStationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'weatherstation_widget',
      '#variables' => array(),
    );
  }

}
