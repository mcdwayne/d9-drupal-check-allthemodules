<?php

/**
 * @file
 * Contains \Drupal\config_override\ModuleConfigOverrides.
 */

namespace Drupal\config_override;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class ModuleConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * Creates a new ModuleConfigOverrides instance.
   *
   * @param string $root
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   */
  public function __construct($root, ModuleHandlerInterface $moduleHandler, CacheBackendInterface $cacheBackend) {
    $this->root = $root;
    $this->moduleHandler = $moduleHandler;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if ($config = $this->cacheBackend->get('config_overrides.modules')) {
      $overrides = $config->data;
    }
    else {
      $modules = $this->moduleHandler->getModuleList();

      foreach ($modules as $module) {
        $folder = $this->root . '/' . $module->getPath() . '/config/override';
        if (file_exists($folder)) {
          $file_storage = new FileStorage($folder);
          $overrides = NestedArray::mergeDeep($overrides, $file_storage->readMultiple($file_storage->listAll()));
        }
      }
      $this->cacheBackend->set('config_overrides.modules', $overrides);
    }

    return array_intersect_key($overrides, array_flip($names));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'config_override.modules';
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
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(['extensions']);
    return $cache_metadata;
  }

}
