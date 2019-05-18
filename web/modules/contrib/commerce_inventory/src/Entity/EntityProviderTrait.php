<?php

namespace Drupal\commerce_inventory\Entity;

/**
 * Provides a trait for entities that have an Inventory provider.
 *
 * @see \Drupal\user\EntityOwnerInterface
 */
trait EntityProviderTrait {

  /**
   * The loaded Inventory Provider.
   *
   * @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface
   */
  protected $inventoryProvider;

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    if (is_null($this->inventoryProvider)) {

      $this->inventoryProvider = \Drupal::service('plugin.manager.commerce_inventory_provider')->createInstance($this->bundle());;
    }
    return $this->inventoryProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getProvider();
  }

}
