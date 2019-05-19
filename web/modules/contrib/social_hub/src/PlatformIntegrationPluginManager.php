<?php

namespace Drupal\social_hub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_hub\Annotation\PlatformIntegration;

/**
 * Manager class for platform plugins.
 */
class PlatformIntegrationPluginManager extends DefaultPluginManager {

  /**
   * Constructs SocialPlatformPluginManager object.
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
    parent::__construct(
      'Plugin/SocialHub/PlatformIntegration',
      $namespaces,
      $module_handler,
      PlatformIntegrationPluginInterface::class,
      PlatformIntegration::class
    );
    $this->alterInfo('social_hub_platform_integration_info');
    $this->setCacheBackend($cache_backend, 'social_hub_platform_integration_plugins');
  }

  /**
   * Get plugins definitions as options.
   *
   * Typically to be used as select/checkboxes/radios element options.
   *
   * @return array
   *   An array of id => label pairs of plugins.
   */
  public function getPluginsAsOptions() {
    $definitions = $this->getDefinitions();
    $plugins = array_column($definitions, 'label', 'id');
    asort($plugins);

    return $plugins;
  }

}
