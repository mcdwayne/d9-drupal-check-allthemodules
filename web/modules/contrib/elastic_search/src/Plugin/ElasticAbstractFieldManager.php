<?php

namespace Drupal\elastic_search\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 *
 *
 * @see plugin_api
 */
class ElasticAbstractFieldManager extends DefaultPluginManager {

  /**
   * ElasticAbstractFieldManager constructor.
   *
   * @param \Traversable                                  $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface      $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ElasticAbstractField',
      $namespaces,
      $module_handler,
      'Drupal\elastic_search\Plugin\ElasticAbstractField\ElasticAbstractFieldInterface',
      'Drupal\elastic_search\Annotation\ElasticAbstractField'
    );
    $this->alterInfo('elastic_search');
    $this->setCacheBackend($cache_backend,
                           'elastic_search');
  }

}
