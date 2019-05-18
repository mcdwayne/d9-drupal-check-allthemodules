<?php

namespace Drupal\odoo_api_entity_sync\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException;

/**
 * Provides the Odoo entity sync plugin manager.
 */
class EntitySyncPluginManager extends DefaultPluginManager implements EntitySyncPluginManagerInterface {

  /**
   * Constructs a new EntitySyncManager object.
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
    parent::__construct('Plugin/OdooEntitySync', $namespaces, $module_handler, 'Drupal\odoo_api_entity_sync\Plugin\EntitySyncInterface', 'Drupal\odoo_api_entity_sync\Annotation\OdooEntitySync');

    $this->alterInfo('odoo_api_entity_sync_odoo_api_entity_sync_info');
    $this->setCacheBackend($cache_backend, 'odoo_api_entity_sync_odoo_api_entity_sync_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceByType($entity_type, $odoo_model, $export_type = 'default') {
    foreach ($this->getDefinitions() as $definition) {
      // @TODO: Cache instances somehow?
      if ($definition['entityType'] == $entity_type
        && $definition['odooModel'] == $odoo_model
        && $definition['exportType'] == $export_type) {
        return $this->createInstance($definition['id']);
      }
    }

    throw new MissingPluginException();
  }

}
