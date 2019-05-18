<?php
/**
 * @file
 * Contains \Drupal\collect\Relation\RelationPluginManager.
 */

namespace Drupal\collect\Relation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for relation plugins.
 */
class RelationPluginManager extends DefaultPluginManager implements RelationPluginManagerInterface {

  /**
   * Constructs a new RelationPluginManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/collect/Relation', $namespaces, $module_handler, 'Drupal\collect\Relation\RelationPluginInterface', 'Drupal\collect\Annotation\Relation');
    $this->setCacheBackend($cache_backend, 'collect_relation_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'generic';
  }

}
