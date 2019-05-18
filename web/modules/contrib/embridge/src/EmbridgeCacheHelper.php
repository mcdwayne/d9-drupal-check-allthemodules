<?php
/**
 * @file
 * Contains \Drupal\embridge\EmbridgeCacheHelper.
 */

namespace Drupal\embridge;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Helper methods for cache paths with embridge_cache.
 *
 * This is placed in the embridge module so all modules have access to it even
 * if the embridge_cache module is off.
 */
class EmbridgeCacheHelper {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new PathProcessorImageStyles object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * Builds a stream wrapper uri to a cached asset.
   *
   * @param \Drupal\embridge\EmbridgeCatalogInterface $catalog
   *   The catalog the target belongs to.
   * @param \Drupal\embridge\EmbridgeAssetEntityInterface $asset
   *   The asset.
   * @param string $scheme
   *   The scheme of the file system.
   * @param string $conversion
   *   The conversion to use.
   *
   * @return string
   *   The stream wrapper uri.
   */
  public function buildUri(EmbridgeCatalogInterface $catalog, EmbridgeAssetEntityInterface $asset, $scheme, $conversion) {
    $target = rtrim($asset->getSourcePath(), '/') . '/' . $asset->getFilename();
    $uri_parts = [
      $catalog->id(),
      $conversion,
      $scheme,
      $target,
    ];
    $cached_uri = $scheme . '://embridge_cache/' . implode('/', $uri_parts);

    return $cached_uri;
  }

  /**
   * Builds a drupal root relative url to a cached asset.
   *
   * @param \Drupal\embridge\EmbridgeCatalogInterface $catalog
   *   The catalog the target belongs to.
   * @param \Drupal\embridge\EmbridgeAssetEntityInterface $asset
   *   The asset.
   * @param string $scheme
   *   The scheme of the file system.
   * @param string $conversion
   *   The conversion to use.
   *
   * @return string
   *   The url to the cached asset relative to the drupal root.
   */
  public function buildUrl(EmbridgeCatalogInterface $catalog, EmbridgeAssetEntityInterface $asset, $scheme, $conversion) {
    $uri = $this->buildUri($catalog, $asset, $scheme, $conversion);
    $directory = $this->streamWrapperManager->getViaScheme($scheme)->getDirectoryPath();
    $target = file_uri_target($uri);

    return '/' . $directory . '/' . $target;
  }

}
