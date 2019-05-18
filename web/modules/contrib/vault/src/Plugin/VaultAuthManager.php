<?php

namespace Drupal\vault\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Vault Authentication plugin manager.
 */
class VaultAuthManager extends DefaultPluginManager {

  /**
   * Constructs a new VaultAuthManager object.
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
    parent::__construct('Plugin/VaultAuth', $namespaces, $module_handler, 'Drupal\vault\Plugin\VaultAuthInterface', 'Drupal\vault\Annotation\VaultAuth');

    $this->alterInfo('vault_vault_auth_info');
    $this->setCacheBackend($cache_backend, 'vault_vault_auth_plugins');
  }

}
