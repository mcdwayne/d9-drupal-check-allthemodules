<?php

/**
 * @file
 * Contains \Drupal\article\Plugin\Block\XaiBlock.
 */

namespace Drupal\ops\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'OnPageSearch' block.
 *
 * @Block(
 *   id = "on_page_search_block",
 *   admin_label = @Translation("On Page Search block"),
 *   category = @Translation("On Page Search Block")
 * )
 */
class OnPageSearch extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'block__on_page_search',
      '#attached' => array(
        'library' => array('ops/onpagesearch'),
      ),
    );
  }

}
