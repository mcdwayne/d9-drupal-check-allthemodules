<?php

namespace Drupal\dat;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the DAT Adminer plugin manager.
 */
class DatAdminerPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new DatAdminerPluginManager object.
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
    parent::__construct('Plugin/Dat/Adminer', $namespaces, $module_handler, 'Drupal\dat\DatAdminerPluginInterface', 'Drupal\dat\Annotation\DatAdminerPlugin');
    $this->alterInfo('dat_adminer_plugin_info');
    $this->setCacheBackend($cache_backend, 'dat_adminer_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    uasort($definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $definitions;
  }

}
