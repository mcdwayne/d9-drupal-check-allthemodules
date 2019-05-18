<?php

namespace Drupal\sdk;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sdk\Annotation\Sdk;

/**
 * Manager of SDK plugins.
 *
 * @method SdkPluginBase createInstance($plugin_id, array $configuration = [])
 * @method SdkPluginDefinition getDefinition($plugin_id, $exception_on_invalid = TRUE)
 * @method SdkPluginDefinition[] getDefinitions()
 */
class SdkPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Sdk', $namespaces, $module_handler, SdkPluginBase::class, Sdk::class);

    $this->setCacheBackend($cache_backend, 'sdk_plugins');
  }

}
