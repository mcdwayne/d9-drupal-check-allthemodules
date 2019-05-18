<?php

namespace Drupal\futurama\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Futurama' block.
 *
 * @Block (
 *  id = "futurama_block",
 *  category = @Translation("Futurama"),
 *  admin_label = @Translation("Futurama quote of the day"),
 *  module = "futurama"
 *  )
 */
class FuturamaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $futurama_title_captions = futurama_title_captions();
    $rand = array_rand($futurama_title_captions, 1);
    return [
      '#markup' => $futurama_title_captions[$rand],
    ];
  }

}
