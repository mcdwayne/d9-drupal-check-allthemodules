<?php
/**
 * @file
 * Contains Drupal\block_render\Response\AssetResponseInterface.
 */

namespace Drupal\block_render\Response;

/**
 * The asset response data.
 */
interface AssetResponseInterface {

  /**
   * Returns the asset libraries.
   *
   * @return \Drupal\block_render\Libraries\Libraries
   *   A library response object.
   */
  public function getLibraries();

  /**
   * Returns the header assets.
   *
   * @return array
   *   Array of assets.
   */
  public function getHeader();

  /**
   * Returns the footer assets.
   *
   * @return array
   *   Array of footer assets.
   */
  public function getFooter();

}
