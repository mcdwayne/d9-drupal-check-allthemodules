<?php

namespace Drupal\tfa;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The send plugin manager.
 */
class TfaSendPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new TfaSend plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TfaSend', $namespaces, $module_handler, 'Drupal\tfa\TfaSendInterface', 'Drupal\tfa\Annotation\TfaSend');
    $this->alterInfo('tfa_send_info');
    $this->setCacheBackend($cache_backend, 'tfa_send');
  }

}
