<?php

namespace Drupal\commerce_inventory\Entity;

/**
 * Provides an interface for defining entities that have an Inventory provider.
 */
interface EntityProviderInterface {

  /**
   * Gets this entity's Inventory Provider instance.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface
   *   The Inventory Provider plugin instance.
   */
  public function getProvider();

}
