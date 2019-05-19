<?php

namespace Drupal\views_polygon_search;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an ViewsPolygonSearchPlugin plugin manager.
 */
class ViewsPolygonSearchPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ViewsPolygonSearchPlugin',
      $namespaces,
      $module_handler,
      'Drupal\views_polygon_search\ViewsPolygonSearchPluginInterface',
      'Drupal\views_polygon_search\Annotation\ViewsPolygonSearchPlugin'
    );
    $this->alterInfo('views_polygon_search_plugin_info');
    $this->setCacheBackend($cache_backend, 'example_plugin_info_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
