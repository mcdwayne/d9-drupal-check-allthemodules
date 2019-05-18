<?php

namespace Drupal\fillpdf;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines the required interface for all FillPDF BackendService plugins.
 */
class FillPdfBackendManager extends DefaultPluginManager {

  /**
   * Constructs a FillPdfBackendManager object.
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
    parent::__construct('Plugin/FillPdfBackend', $namespaces, $module_handler, '\Drupal\fillpdf\FillPdfBackendPluginInterface');

    $this->alterInfo('fillpdf_backend_info');
    $this->setCacheBackend($cache_backend, 'fillpdf_backend_info_plugins');
  }

  /**
   * Gets the definitions of all FillPDF backend plugins.
   *
   * @return mixed[]
   *   An associative array of plugin definitions, keyed by plugin ID and sorted
   *   by weight.
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();

    foreach ($definitions as $id => $definition) {
      if (!isset($definition['description'])) {
        $definitions[$id]['description'] = '';
      }
      if (!isset($definition['weight'])) {
        $definitions[$id]['weight'] = 0;
      }
    }

    uasort($definitions, function ($a, $b) {
      return $a['weight'] - $b['weight'];
    });

    return $definitions;
  }

}
