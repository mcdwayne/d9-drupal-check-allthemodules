<?php

namespace Drupal\uvrp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'Ubercart Recently Viewed Products' Block.
 *
 * @Block(
 *   id = "uvrp",
 *   admin_label = @Translation("Ubercart Recently Viewed Products"),
 * )
 */
class RVPBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form['#markup'] = uvrp_block_display();
    $form['#cache']['max-age'] = 0;
    return $form;

  }

}
