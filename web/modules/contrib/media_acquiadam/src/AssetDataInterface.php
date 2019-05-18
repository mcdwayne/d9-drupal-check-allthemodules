<?php
/**
 * @file
 * Acquia DAM Asset Data service interface.
 */

namespace Drupal\media_acquiadam;

/**
 * Defines the asset data service interface.
 */
interface AssetDataInterface {

  /**
   * Returns data stored for an asset.
   *
   * @param int $assetID
   *   The ID of the asset that data is associated with.
   * @param string $name
   *   (optional) The name of the data key.
   *
   * @return mixed|array
   *   The requested asset data, depending on the arguments passed:
   *     - If $name was provided then the stored value is returned, or NULL if
   *       no value was found.
   *     - If no $name was provided then all data will be returned for the given
   *       asset if found.
   */
  public function get($assetID, $name = NULL);

  /**
   * Stores data for an asset.
   *
   * @param int $assetID
   *   The ID of the asset to store data against.
   * @param string $name
   *   The name of the data key.
   * @param mixed $value
   *   The value to store. Non-scalar values are serialized automatically.
   */
  public function set($assetID, $name, $value);

  /**
   * Deletes data stored for an asset.
   *
   * @param int|array $assetID
   *   (optional) The ID of the asset the data is associated with. Can also
   *   be an array to delete the data of multiple assets.
   * @param string $name
   *   (optional) The name of the data key. If omitted, all data associated with
   *   $assetID.
   */
  public function delete($assetID = NULL, $name = NULL);
}
