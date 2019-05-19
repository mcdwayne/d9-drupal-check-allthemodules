<?php

namespace Drupal\views_oai_pmh\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the OAI-PMH Metadata Prefix plugin manager.
 */
class MetadataPrefixManager extends DefaultPluginManager {

  /**
   * Constructs a new MetadataPrefixManager object.
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
    parent::__construct('Plugin/MetadataPrefix', $namespaces, $module_handler, 'Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface', 'Drupal\views_oai_pmh\Annotation\MetadataPrefix');

    $this->alterInfo('views_oai_pmh_views_oai_pmh_prefix_info');
    $this->setCacheBackend($cache_backend, 'views_oai_pmh_views_oai_pmh_prefix_plugins');
  }

}
