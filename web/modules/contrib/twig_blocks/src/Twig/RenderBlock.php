<?php

/**
 * @file
 * Contains \Drupal\twig_blocks\Twig\RenderBlock.
 */

namespace Drupal\twig_blocks\Twig;

/**
 * Adds extension to render a menu
 */
class RenderBlock extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'render_block';
  }

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'render_block',
        [$this, 'render_block'],
        ['is_safe' => ['html']]
      ),
    ];
  }

  /**
   * Provides function to programmatically rendering a block.
   *
   * @param String $block_id
   *   The machine id of the block to render
   */
  public function render_block($block_id) {
    $block = \Drupal\block\Entity\Block::load($block_id);
    $markup = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    return ['#markup' => drupal_render($markup)];
  }
}