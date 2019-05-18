<?php
/**
 * Admin Block with a draggable list of all block
 *
 * @Block(
 *   id = "draggable_blocks_admin",
 *   admin_label = @Translation("Draggable Blocks"),
 * )
 */

namespace Drupal\draggable_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

class AdminBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = _draggable_blocks_control();
    return array(
      '#markup' => \Drupal::service('renderer')->render($render_array),
    );
  }

}
?>