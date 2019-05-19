<?php

namespace Drupal\simple_addthis\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SimpleShareThisBlock' block.
 *
 * @Block(
 *  id = "simple_add_this_block",
 *  admin_label = @Translation("Simple AddThis block"),
 * )
 */
class SimpleAddThisBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['simple_add_this_block'] = [
      '#theme' => 'theme_simple_add_this_block',
      '#attached' => [
        'library' => [
          'simple_addthis/addthis',
        ],
      ],
    ];
    return $build;
  }

}
