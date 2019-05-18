<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaAssetHelperInterface.
 */

namespace Drupal\embridge;

/**
 * Interface EnterMediaAssetHelperInterface.
 *
 * @package Drupal\embridge
 */
interface EnterMediaAssetHelperInterface {
  /**
   * Returns a url for an Embridge Asset entity using a conversion.
   *
   * @param \Drupal\embridge\EmbridgeAssetEntityInterface $asset
   *   The embridge asset entity.
   * @param string $application_id
   *   The application id for the catalog this asset resides in.
   * @param string $conversion
   *   The conversion to get the url for.
   *
   * @return string
   *   The fully qualified url to the asset.
   */
  public function getAssetConversionUrl(EmbridgeAssetEntityInterface $asset, $application_id, $conversion);

  /**
   * Returns a url for an Embridge Asset entity, to download the original file.
   *
   * @param \Drupal\embridge\EmbridgeAssetEntityInterface $asset
   *   The embridge asset entity.
   * @param string $application_id
   *   The application id for the catalog this asset resides in.
   *
   * @return string
   *   The fully qualified url to download the asset.
   */
  public function getAssetDownloadUrl(EmbridgeAssetEntityInterface $asset, $application_id);

  /**
   * Converts a search result to a Embridge Asset Entity.
   *
   * @param array $result
   *   A result from the search results.
   * @param string $catalog_id
   *   A catalog id to relate the asset to.
   *
   * @return EmbridgeAssetEntityInterface
   *   The populated and saved entity.
   */
  public function searchResultToAsset($result, $catalog_id);

  /**
   * Deletes temporary assets.
   */
  public function deleteTemporaryAssets();

}
