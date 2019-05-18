<?php

namespace Drupal\fitbit_views;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Fitbit base table endpoint plugin manager class. Each Fitbit endpoint has a
 * one-to-one mapping with a views base table. Each of which can have a base
 * table endpoint plugin associated with it, which is an object that has
 * specific domain knowledge about the Fitbit endpoint it interacts with. Each
 * plugin is resposibile for communicating with that endpoint and translating
 * the response's into \Drupal\views\ResultRow objects.
 */
class FitbitBaseTableEndpointPluginManager extends DefaultPluginManager {

  /**
   * FitbitBaseTableEndpointPluginManager constructor.
   *
   * @param \Traversable $namespaces
   * @param CacheBackendInterface $cache_backend
   * @param ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FitbitBaseTableEndpoint', $namespaces, $module_handler, 'Drupal\fitbit_views\FitbitBaseTableEndpointInterface', 'Drupal\fitbit_views\Annotation\FitbitBaseTableEndpoint');

    $this->alterInfo('fitbit_base_table_endpoints_info');
    $this->setCacheBackend($cache_backend, 'fitbit_base_table_endpoints');
  }
}
