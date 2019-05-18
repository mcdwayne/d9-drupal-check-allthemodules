<?php

namespace Drupal\commerce_inventory;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Inventory Adjustment type plugin manager.
 */
class InventoryAdjustmentTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new CommerceInventoryAdjustmentTypeManager object.
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
    parent::__construct('Plugin/Commerce/InventoryAdjustmentType', $namespaces, $module_handler, 'Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface', 'Drupal\commerce_inventory\Annotation\CommerceInventoryAdjustmentType');

    $this->alterInfo('commerce_inventory_adjustment_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_inventory_adjustment_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The inventory adjustment type %s must define the %s property.', $plugin_id, $required_property));
      }
    }

    // Related preposition required if related adjustment type is set.
    if (!empty($definition['related_adjustment_type']) && is_null($definition['label_related_preposition'])) {
      throw new PluginException(sprintf('The inventory adjustment type %s must define the %s property.', $plugin_id, 'label_related_preposition'));
    }

    // Added definition defaults.
    $definition += [
      'label_preposition' => t('at'),
      'label_related_preposition' => '',
      'label_verb' => $definition['label'],
      'related_adjustment_type' => '',
      'internal' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions($exlude_internal = FALSE) {
    if ($exlude_internal !== TRUE) {
      return parent::getDefinitions();
    }
    return array_filter(parent::getDefinitions(), function ($definition) {
      return (!array_key_exists('internal', $definition) || $definition['internal'] !== TRUE);
    });
  }

}
