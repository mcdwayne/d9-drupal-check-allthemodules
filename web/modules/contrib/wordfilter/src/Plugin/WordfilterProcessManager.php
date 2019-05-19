<?php

namespace Drupal\wordfilter\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Wordfilter Process plugin manager.
 */
class WordfilterProcessManager extends DefaultPluginManager {

  /**
   * Constructor for WordfilterProcessManager objects.
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
    parent::__construct('Plugin/WordfilterProcess', $namespaces, $module_handler, 'Drupal\wordfilter\Plugin\WordfilterProcessInterface', 'Drupal\wordfilter\Annotation\WordfilterProcess');

    $this->alterInfo('wordfilter_wordfilter_process_info');
    $this->setCacheBackend($cache_backend, 'wordfilter_wordfilter_process_plugins');
  }

}
