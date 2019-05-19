<?php

namespace Drupal\stopwatch\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'StopwatchBlock' block.
 *
 * @Block(
 *  id = "stopwatch_block",
 *  admin_label = @Translation("Stopwatch block"),
 * )
 */
class StopwatchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'stopwatch_block',
      '#attached' => array(
        'library' => array('stopwatch/stopwatch_block'),
      ),
    );
  }

}
