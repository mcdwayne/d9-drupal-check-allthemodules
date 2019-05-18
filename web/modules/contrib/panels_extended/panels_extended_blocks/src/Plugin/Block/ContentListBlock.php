<?php

namespace Drupal\panels_extended_blocks\Plugin\Block;

use Drupal\panels_extended_blocks\BlockConfig\FixedNodesConfig;
use Drupal\panels_extended_blocks\BlockConfig\NodeTypeFilter;
use Drupal\panels_extended_blocks\BlockConfig\NrOfItemsLimiter;
use Drupal\panels_extended_blocks\BlockConfig\PreventNodeDuplication;
use Drupal\panels_extended_blocks\BlockConfig\TermFilter;
use Drupal\panels_extended_blocks\NodeListBlockBase;

/**
 * The block defining a basic implementation to fetch recent content.
 *
 * @Block(
 *   id = "panels_extended_blocks_content_list",
 *   admin_label = "Content List Block",
 *   category = "Panels Extended"
 * )
 */
class ContentListBlock extends NodeListBlockBase {

  /**
   * {@inheritdoc}
   */
  public function getNumberOfItems() {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBlockConfigsToAdd() {
    return [
      new TermFilter($this, [
        'tags' => 'Tags',
      ]),
      new FixedNodesConfig($this),
      new NrOfItemsLimiter($this, [5, 10, 15, 20]),
      new NodeTypeFilter($this),
      new PreventNodeDuplication($this),
    ];
  }

}
