<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\FileCompressorManager.
 */

namespace Drupal\file_compressor_field\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines the plugin manager for File Compressor backends.
 */
class FileCompressorManager extends DefaultPluginManager {

  /**
   * Constructs a new \Drupal\file_compressor_field\Plugin\FileCompressorManager
   * object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FileCompressor', $namespaces, $module_handler, 'Drupal\file_compressor_field\Plugin\FileCompressorPluginInterface', 'Drupal\file_compressor_field\Annotation\FileCompressor');

    $this->alterInfo('file_compressor');
    $this->setCacheBackend($cache_backend, 'file_compressor_plugins');
  }

}
