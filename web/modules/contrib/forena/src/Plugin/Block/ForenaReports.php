<?php

/**
 * @file
 * Contains \Drupal\forena\Plugin\Block\ForenaReports.
 */

namespace Drupal\forena\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides the ForenaReports block.
 *
 * @Block(
 *   id = "forena_reports",
 *   admin_label = @Translation("My reports")
 * )
 */
class ForenaReports extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /**
     * @FIXME
     * hook_block_view() has been removed in Drupal 8. You should move your
     * block's code into this method and delete forena_block_view()
     * as soon as possible!
     */
    return forena_block_view('forena_reports');
  }

  
}
