<?php

namespace Drupal\commerce_inventory_store\AvailabilityChecker;

use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\commerce_inventory_store\InventoryStoreManager;

/**
 * Commerce Inventory availability checker.
 */
class InventoryAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * The inventory Commerce Store manager.
   *
   * @var \Drupal\commerce_inventory_store\InventoryStoreManager
   */
  protected $inventoryStoreManager;

  /**
   * The quantity available manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityAvailable;

  /**
   * The quantity minimum manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityMinimum;

  /**
   * Constructs a new InventoryAvailabilityChecker object.
   *
   * @param \Drupal\commerce_inventory_store\InventoryStoreManager $inventory_store_manager
   *   The inventory Commerce Store manager.
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_available
   *   The quantity available manager.
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_minimum
   *   The quantity minimum manager.
   */
  public function __construct(InventoryStoreManager $inventory_store_manager, QuantityManagerInterface $quantity_available, QuantityManagerInterface $quantity_minimum) {
    $this->inventoryStoreManager = $inventory_store_manager;
    $this->quantityAvailable = $quantity_available;
    $this->quantityMinimum = $quantity_minimum;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $entity) {
    // @todo toggle on entity stating whether it is managed (or not managed)
    // Always return true, since the context of the request isn't supplied.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Loads applicable.
    $item_ids = $this->inventoryStoreManager->getStoreItemIds($entity, $context->getStore());
    $total_quantity = 0;

    foreach ($item_ids as $item_id) {
      $total_quantity += $this->quantityAvailable->getQuantity($item_id);
      if ($total_quantity >= $quantity) {
        return TRUE;
        // @todo Return \Drupal\commerce\AvailabilityResponse::available(0, $total_quantity);.
      }
    }

    // No opinion.
    return FALSE;
    // @todo Return \Drupal\commerce\AvailabilityResponse::unavailable(0, 0, 'Out of stock');.
  }

}
