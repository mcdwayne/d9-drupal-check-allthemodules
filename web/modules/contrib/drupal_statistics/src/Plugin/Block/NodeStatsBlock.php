<?php

namespace Drupal\drupal_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\drupal_statistics\DrupalStatisticsHelper;

/**
 * Provides a block for node statistics.
 *
 * @Block(
 *   id = "node_stats_block",
 *   admin_label = @Translation("Node Statistics block")
 * )
 */
class NodeStatsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $instance = DrupalStatisticsHelper::instance();
    $data_count = $instance->getNodeVisitCount();
    $data_count == -1 ? $render = "not a node" : $render = "Visited by " . $data_count . " users";
    return [
      '#type' => 'markup',
      '#markup' => $render,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
