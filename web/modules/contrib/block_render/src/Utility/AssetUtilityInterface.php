<?php
/**
 * @file
 * Contains Drupal\block_render\Utility\AssetUtilityInterface.
 */

namespace Drupal\block_render\Utility;

use Drupal\Core\Asset\AttachedAssetsInterface;

/**
 * A utility to retrieve necessary assets.
 */
interface AssetUtilityInterface {

  /**
   * Retrieves the Asset Response for a set of assets.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   An attached assets object.
   *
   * @return \Drupal\block_render\Data\AssetReponse
   *   An asset response object.
   */
  public function getAssetResponse(AttachedAssetsInterface $assets);

}
