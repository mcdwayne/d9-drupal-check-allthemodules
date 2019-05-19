<?php

namespace Drupal\wsdata\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Web Service Encoder plugin manager.
 */
class WSEncoderManager extends DefaultPluginManager {

  /**
   * Constructor for WSEncoderManager objects.
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
    parent::__construct('Plugin/WSEncoder', $namespaces, $module_handler, 'Drupal\wsdata\Plugin\WSEncoderInterface', 'Drupal\wsdata\Annotation\WSEncoder');

    $this->alterInfo('wsdata_wsencoder_info');
    $this->setCacheBackend($cache_backend, 'wsdata_wsencoder_plugins');
  }

}
