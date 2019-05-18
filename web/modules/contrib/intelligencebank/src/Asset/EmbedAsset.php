<?php

namespace Drupal\ib_dam\Asset;

/**
 * Class EmbedAsset.
 *
 * Holds logic for embed asset type.
 *
 * @package Drupal\ib_dam\Asset
 */
class EmbedAsset extends Asset implements EmbedAssetInterface {

  /**
   * Asset type.
   *
   * @var string
   */
  protected static $sourceType = 'embed';

  /**
   * The display settings of an embed asset, like width, height, etc.
   *
   * @var array
   */
  private $displaySettings;

  /**
   * The remote url of an embed asset.
   *
   * @var null|string
   */
  private $remoteUrl;

  /**
   * {@inheritdoc}
   */
  public static function getApplicableValidators() {
    return [
      'validateIsAllowedResourceType',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->remoteUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->remoteUrl = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPreview() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplaySettings() {
    return $this->displaySettings ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySettings(array $settings = []) {
    $this->displaySettings = $settings;
    return $this;
  }
}
