<?php

namespace Drupal\chunker;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Manages chunker method plugins.
 *
 * @see chunker.services.yml
 * @see \Drupal\chunker\Annotation\ChunkerMethod
 * @see \Drupal\chunker\ChunkerMethodInterface
 * @see \Drupal\chunker\ChunkerMethodBase
 * @see plugin_api
 */
class ChunkerMethodManager extends DefaultPluginManager {

  /**
   * Constructs a new ChunkerMethodManager.
   *
   * Used by the service to return chunker methods.
   *
   * @param \Traversable $namespaces
   *   Where look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ChunkerMethod', $namespaces, $module_handler, 'Drupal\chunker\ChunkerMethodInterface', 'Drupal\chunker\Annotation\ChunkerMethod');
    $this->setCacheBackend($cache_backend, 'chunker_methods');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
