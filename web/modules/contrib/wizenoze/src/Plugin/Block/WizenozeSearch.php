<?php

namespace Drupal\wizenoze\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'WizenozeSearch' block.
 *
 * @Block(
 *   id = "wizenoze_search_default",
 *   admin_label = @Translation("Wizenoze Search Default"),
 *   category = @Translation("Custom Wizenoze Search Block")
 * )
 */
class WizenozeSearch extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $query = NULL;

    $build = [
      '#theme' => 'wizenoze_search_default',
      '#query' => $query,
      '#cache' => ['contexts' => ['url.path']],
    ];
    return $build;
  }

}
