<?php

namespace Drupal\scheduled_executable;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of group resolver plugins.
 *
 * @see \Drupal\commerce_license\Annotation\CommerceLicenseType
 * @see plugin_api
 */
class ResolverManager extends DefaultPluginManager {

  /**
   * Constructs a new LicenseTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ScheduledExecutable/Resolver',
      $namespaces,
      $module_handler,
      'Drupal\scheduled_executable\Plugin\ScheduledExecutable\Resolver\ResolverInterface',
      'Drupal\scheduled_executable\Annotation\ScheduledExecutableResolver'
    );

    // TODO: needs docs!
    $this->alterInfo('scheduled_executable_resolver_info');
    $this->setCacheBackend($cache_backend, 'scheduled_executable_resolver_plugins');
  }

}
