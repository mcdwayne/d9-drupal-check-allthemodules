<?php
/**
 * @file
 * Contains Drupal\block_render\Response\BlockResponseInterface.
 */

namespace Drupal\block_render\Response;

/**
 * The block response data.
 */
interface BlockResponseInterface {

  /**
   * Returns the asset response.
   *
   * @return \Drupal\block_render\Response\AssetResponseInterface
   *   An asset response object.
   */
  public function getAssets();

  /**
   * Returns the content response.
   *
   * @return \Drupal\block_render\Content\RenderedContentInterface
   *   A rendred content object.
   */
  public function getContent();

}
