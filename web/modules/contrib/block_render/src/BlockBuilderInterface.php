<?php
/**
 * @file
 * Contains Drupal\block_render\BlockBuiler.
 */

namespace Drupal\block_render;

use Drupal\block\BlockInterface;

/**
 * Build a block from a given id.
 */
interface BlockBuilderInterface {

  /**
   * Builds multiple blocks.
   *
   * @param \Drupal\block\BlockInterface $block
   *   Block to render.
   * @param array $loaded
   *   Libraries that have already been loaded.
   *
   * @return array
   *   An array of content and assets to be rendered.
   */
  public function build(BlockInterface $block, array $loaded = array());

  /**
   * Builds multiple blocks.
   *
   * @param array $blocks
   *   Array of Blocks to render.
   * @param array $loaded
   *   Libraries that have already been loaded.
   * @param bool $single
   *   Indicator if a single item is being returned.
   *
   * @return array
   *   An array of content and assets to be rendered.
   */
  public function buildMultiple(array $blocks, array $loaded = array(), $single = FALSE);

}
