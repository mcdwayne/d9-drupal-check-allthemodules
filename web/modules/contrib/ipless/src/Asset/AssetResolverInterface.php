<?php

namespace Drupal\ipless\Asset;

use Drupal\Core\Asset\AttachedAssetsInterface;

/**
 * Interface for AssetResolverInterface.
 */
interface AssetResolverInterface {

  /**
   * Returns the CSS assets for the current response's libraries.
   * 
   * @param Drupal\Core\Asset\AttachedAssetsInterface $assets
   */
  public function getLessAssets(AttachedAssetsInterface $assets);

}
