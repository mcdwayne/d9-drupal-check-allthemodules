<?php

/**
 * @file
 * Contains \Drupal\pagerank_widget\Plugin\Block\PagerankWidgetFancyBlock.
 */

namespace Drupal\pagerank_widget\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'PageRank' block.
 *
 * @Block(
 *   id = "pagerank_widget_fancy_block",
 *   admin_label = @Translation("PageRank")
 * )
 */
class PagerankWidgetFancyBlock extends BlockBase {
  public function build() {
    $build = array(
        '#theme' => 'pagerank_widget_fancy_block'
      );
    return $build;
  }
}
