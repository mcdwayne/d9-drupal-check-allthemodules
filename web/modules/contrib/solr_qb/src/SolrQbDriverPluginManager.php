<?php
/**
 * @file
 * Contains \Drupal\solr_qb\SolrQbDriverPluginManager.
 */

namespace Drupal\solr_qb;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class SolrQbDriverPluginManager extends DefaultPluginManager implements PluginManagerInterface {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SolrQbDriver', $namespaces, $module_handler, 'Drupal\solr_qb\Plugin\SolrQbDriverInterface', 'Drupal\solr_qb\Annotation\SolrQbDriver');
    $this->alterInfo('solr_qb_driver_info');
    $this->setCacheBackend($cache_backend, 'solr_qb_driver_plugins');
  }

}
