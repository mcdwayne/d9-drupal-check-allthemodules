<?php

namespace Drupal\commerce_inventory;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Inventory Provider plugin manager.
 */
class InventoryProviderManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * Constructs a new InventoryProviderManager object.
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
    parent::__construct('Plugin/Commerce/InventoryProvider', $namespaces, $module_handler, 'Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface', 'Drupal\commerce_inventory\Annotation\CommerceInventoryProvider');

    $this->alterInfo('commerce_inventory_provider_info');
    $this->setCacheBackend($cache_backend, 'commerce_inventory_provider_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The plugin %s must define the %s property.', $plugin_id, $required_property));
      }
    }

    // Added definition defaults.
    $definition += [
      'category' => $this->t('Local'),
    ];
  }

}
