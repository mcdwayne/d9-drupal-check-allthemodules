<?php
/**
 * @file
 * Contains Drupal\block_render\Utility\LibraryUtilityInterface.
 */

namespace Drupal\block_render\Utility;

use Drupal\Core\Asset\AttachedAssetsInterface;

/**
 * A utility to retrieve necessary libraries.
 */
interface LibraryUtilityInterface {

  /**
   * Retrieves the Libraries for a set of assets.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   An attached assets object.
   *
   * @return \Drupal\block_render\Data\LibraryReponse
   *   An asset response object.
   */
  public function getLibraryResponse(AttachedAssetsInterface $assets);

}
