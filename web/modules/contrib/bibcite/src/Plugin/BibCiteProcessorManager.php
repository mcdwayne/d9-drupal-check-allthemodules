<?php

namespace Drupal\bibcite\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Processor plugin manager.
 */
class BibCiteProcessorManager extends DefaultPluginManager {

  /**
   * Constructor for BibCiteProcessorManager objects.
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
    parent::__construct('Plugin/BibCiteProcessor', $namespaces, $module_handler, 'Drupal\bibcite\Plugin\BibCiteProcessorInterface', 'Drupal\bibcite\Annotation\BibCiteProcessor');

    $this->alterInfo('bibcite_bibcite_processor_info');
    $this->setCacheBackend($cache_backend, 'bibcite_bibcite_processor_plugins');
  }

}
