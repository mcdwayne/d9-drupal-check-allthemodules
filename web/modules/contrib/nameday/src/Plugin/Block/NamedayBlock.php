<?php

/**
 * @file
 * Contains \Drupal\nameday\Plugin\Block\NamedayBlock.
 */

namespace Drupal\nameday\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'NamedayBlock' block.
 *
 * @Block(
 *  id = "nameday_block",
 *  admin_label = @Translation("Name day"),
 * )
 */
class NamedayBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $data = _nameday_get_row();

    if (!empty($data)) {
      $build['nameday_block'] = [
        '#theme'   => 'nameday',
        '#name'    => $data['name'],
        '#holiday' => $data['holiday'],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable cache.
    return 0;
  }

}
