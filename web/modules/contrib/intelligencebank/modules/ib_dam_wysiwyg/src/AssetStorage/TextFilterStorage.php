<?php

namespace Drupal\ib_dam_wysiwyg\AssetStorage;

use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Asset\EmbedAssetInterface;
use Drupal\ib_dam\Asset\LocalAssetInterface;
use Drupal\ib_dam\AssetStorage\AssetStorageInterface;

/**
 * Class TextFilterStorage.
 *
 * AssetStorage type for text_format.
 *
 * Knows how to (un)serialize data from text:
 *
 * @package Drupal\ib_dam_wysiwyg\AssetStorage
 */
class TextFilterStorage implements AssetStorageInterface {

  const VALUES_PATTERN = '/(<p>)?(?<json>{(?=.*source_type\b)(?=.*type)(?=.*name)(?=.*display_settings)(?=.*remote_url\b|.*file_id\b)(?=.*preview_uri\b)(.*)})(<\/p>)?/';

  /**
   * MediaStorage constructor.
   *
   * @param string $storage_key
   *   The storage id, consists of storage class, source type, media type id.
   */
  public function __construct($storage_key) {}

  /**
   * {@inheritdoc}
   */
  public function createStorage(AssetInterface $asset) {
    $data = [
      // Maybe we need generate preview on backend,
      // and send as special data to the front?
      'source_type' => $asset->getSourceType(),
      'type' => $asset->getType(),
      'name' => $asset->getName(),
      'display_settings' => [],
    ];
    if ($asset instanceof EmbedAssetInterface) {
      $data['remote_url'] = $asset->getUrl();
      // @todo: figure out how to deal with preview of non-image based remote assets.
      $data['preview_uri'] = $asset->getUrl();
    }
    elseif ($asset instanceof LocalAssetInterface) {
      $data['file_id'] = $asset->localFile()->id();
      $data['preview_uri'] = file_url_transform_relative(file_create_url($asset->localFile()->getFileUri()));
    }
    return $data;
  }

}
