<?php

namespace Drupal\annotations\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to display footnotes.
 *
 * @Block(
 *   id = "annotations_footnotes",
 *   admin_label = @Translation("Annotations Footnotes block")
 * )
 */
class FootnotesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '',
    ];
  }

}
