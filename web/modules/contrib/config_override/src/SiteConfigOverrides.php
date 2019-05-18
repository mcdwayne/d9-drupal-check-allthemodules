<?php

namespace Drupal\config_override;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

class SiteConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Constants for the override directory.
   */
  const CONFIG_OVERRIDE_DIRECTORY = 'override';

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Creates a new SiteConfigOverrides instance.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   */
  public function __construct($root, CacheBackendInterface $cacheBackend) {
    $this->root = $root;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (!$directory = $this->getSiteConfigOverrideFolder()) {
      return $overrides;
    }

    if ($config = $this->cacheBackend->get('config_overrides.site')) {
      $overrides = $config->data;
    }
    else {
      $storage = new FileStorage($this->getSiteConfigOverrideFolder());
      $overrides = $storage->readMultiple($storage->listAll());

      $this->cacheBackend->set('config_overrides.site', $overrides);
    }
    return array_intersect_key($overrides, array_flip($names));
  }

  /**
   * Returns the site config overrides directory or NULL if it was not defined.
   *
   * @return string|null
   *   The site config overrides directory or NULL if it was not defined.
   */
  protected function getSiteConfigOverrideFolder() {
    try {
      return $this->root . '/' . config_get_config_directory(static::CONFIG_OVERRIDE_DIRECTORY);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'config_override.site';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }


}
