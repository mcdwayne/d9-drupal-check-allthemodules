<?php

namespace Drupal\commerce_inventory\Plugin\Action;

/**
 * Sync an Inventory Item's quantity to its provider.
 *
 * @Action(
 *   id = "commerce_inventory_item_sync_to_provider",
 *   label = @Translation("Sync quantity to the Provider."),
 * )
 */
class InventoryItemSyncToProvider extends InventoryItemSyncFromProvider {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->inventoryItemStorage->syncQuantityToProvider($entities);
  }

}
