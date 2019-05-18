<?php

namespace Drupal\better_subthemes_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to test Better sub-themes block layout inheritance.
 *
 * @Block(
 *   id = "better_subthemes_test_block",
 *   admin_label = @Translation("Better sub-themes - Test block")
 * )
 */
class BetterSubthemesTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'Better sub-themes - Test block'];
  }

}
