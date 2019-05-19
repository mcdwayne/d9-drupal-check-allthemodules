<?php

/**
 * @file
 * Contains \Drupal\track_site_visitor\Plugin\Block\TSVCurrentLocation.
 */

namespace Drupal\track_site_visitor\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TSVCurrentLocation' block.
 *
 * @Block(
 *  id = "tsvcurrent_location",
 *  admin_label = @Translation("Current Location"),
 * )
 */
class TSVCurrentLocation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['current_location']['#markup'] = '<div class="tsv-current-location"></div>';

    return $build;
  }

}
