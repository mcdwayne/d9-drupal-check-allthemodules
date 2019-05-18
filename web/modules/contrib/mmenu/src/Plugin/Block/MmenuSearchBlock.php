<?php

namespace Drupal\mmenu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Search block.
 *
 * @Block(
 *   id = "mmenu:search",
 *   admin_label = @Translation("Search")
 * )
 */
class MmenuSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\search\Form\SearchBlockForm');
  }

}
