<?php

namespace Drupal\kudos_slider\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides kudos_slider block.
 *
 * @Block(
 *   id = "kudos_slider",
 *   admin_label = @Translation("Kudos Slider"),
 *   category = @Translation("Blocks")
 * )
 */
class SliderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => kudos_slider_homepage(),
      '#attached' => array(
        'library' => array(
          'kudos_slider/slider-styling',
        ),
      ),
    );
  }

}
