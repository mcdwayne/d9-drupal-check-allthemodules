<?php

namespace Drupal\warmer\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\warmer\Annotation\Warmer;

/**
 * Manager for the warmer plugins.
 */
class WarmerPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new HookPluginManager object.
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
    $this->alterInfo(FALSE);
    parent::__construct('Plugin/warmer', $namespaces, $module_handler, WarmerInterface::class, Warmer::class);
    $this->setCacheBackend($cache_backend, 'warmer_plugins');
  }

  /**
   * Instantiates all the warmer plugins.
   *
   * @return \Drupal\warmer\Plugin\WarmerPluginBase[]
   *   The plugin instances.
   */
  public function getWarmers($plugin_ids = NULL) {
    if (!$plugin_ids) {
      $definitions = $this->getDefinitions();
      $plugin_ids = array_map(function ($definition) {
        return empty($definition) ? NULL : $definition['id'];
      }, $definitions);
      $plugin_ids = array_filter(array_values($plugin_ids));
    }
    $warmers = array_map(function ($plugin_id) {
      try {
        return $this->createInstance($plugin_id);
      }
      catch (PluginException $exception) {
        return NULL;
      }
    }, $plugin_ids);
    return array_filter($warmers, function ($warmer) {
      return $warmer instanceof WarmerPluginBase;
    });
  }


}
