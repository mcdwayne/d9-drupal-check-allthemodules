<?php

/**
 * @file
 * Contains \Drupal\block_refresh\Plugin\Block\BlockRefreshBlockBlock.
 */

namespace Drupal\block_refresh\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Block Refresh' block for testing.
 *
 * @Block(
 *   id = "block_refresh_block",
 *   subject = "Block refresh test block",
 *   admin_label = @Translation("Block Refresh"),
 *   module = "block_refresh"
 * )
 */
class BlockRefreshBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#markup' => time(),
    );
  }

}
