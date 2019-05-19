<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;
use Drupal\block\Entity\Block;

/**
 * The plugin for check authenticated user.
 *
 * @TwigPlugin(
 *   id = "twig_extender_get_block",
 *   label = @Translation("Get a block"),
 *   type = "function",
 *   name = "block_view",
 *   function = "getBlock"
 * )
 */
class BlockView extends TwigPluginBase {

  /**
   * Implementation for render block.
   */
  public function getBlock($blockId) {
    $block = Block::load($blockId);
    if (!$block) {
      return;
    }
    $blockContent = \Drupal::entityManager()
      ->getViewBuilder('block')
      ->view($block);
    return \Drupal::service('renderer')->render($blockContent);
  }

}
