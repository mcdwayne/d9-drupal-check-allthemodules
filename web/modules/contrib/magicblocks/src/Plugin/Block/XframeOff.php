<?php

namespace Drupal\magicblocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a X-Frame-Off block.
 *
 * @Block(
 *   id = "magicblocks_x_frame_off",
 *   admin_label = @Translation("X-Frame-Off"),
 *   category = @Translation("Magic Blocks"),
 * )
 */
class XframeOff extends BlockBase {

 /**
  * {@inheritdoc}
  */
  public function build() {
    // For xframe vs frame-ancestors see https://www.drupal.org/project/drupal/issues/2820340
    // @see \Drupal\magicblocks\EventSubscriber\MagicBlocksEventSubscriber::onKernelResponse
    $render['#attached']['http_header'][] = ['magicblocks-overide-x-frame-options', '', TRUE];
    return $render;
  }

}
