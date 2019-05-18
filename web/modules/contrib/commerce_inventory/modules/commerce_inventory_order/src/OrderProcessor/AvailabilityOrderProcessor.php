<?php

namespace Drupal\commerce_inventory_order\OrderProcessor;

use Drupal\commerce\AvailabilityManagerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\commerce_inventory_store\InventoryStoreManager;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides an order processor that removes items that are no longer available.
 */
class AvailabilityOrderProcessor implements OrderProcessorInterface {

  use MessengerTrait;
  use StringTranslationTrait;

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
  public function process(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity) {
        $order_quantity = floatval($order_item->getQuantity());
        $available_quantity = $this->getAvailableQuantity($purchased_entity, $order->getStore(), $order_quantity);

        // Remove order item there is no quantity available.
        if (empty($available_quantity) || $available_quantity < 0) {
          $order->removeItem($order_item);
          $order_item->delete();
          $this->messenger()->addError(
            $this->t('@purchasable is no longer available and has been removed from your order.', [
              '@purchasable' => $purchased_entity->label()
            ])
          );
        }
        // Adjust to max quantity available if over.
        elseif ($available_quantity < $order_quantity) {
          $this->messenger()->addError(
            $this->t('@purchasable only has @quantity available. Your order has been updated.', [
              '@purchasable' => $purchased_entity->label(),
              '@quantity' => $available_quantity,
            ])
          );

          $order_item->setQuantity($available_quantity);
        }
      }
    }
  }

  /**
   * Gets the available quantity of purchasable entity available at the store.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The purchased entity.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   * @param float|null $quantity
   *   A quantity to check against for efficiency. Will fully compile quantity
   *   if NULL.
   *
   * @return float|int
   *   The compiled available quantity for the purchasable entity.
   */
  protected function getAvailableQuantity(PurchasableEntityInterface $purchased_entity, StoreInterface $store, $quantity = NULL) {
    // Load all applicable items.
    $item_ids = $this->inventoryStoreManager->getStoreItemIds($purchased_entity, $store);

    // Compile total quantity available to this store.
    $quantity_total = 0;
    foreach ($item_ids as $item_id) {
      $quantity_available = $this->quantityAvailable->getQuantity($item_id);
      $quantity_minimum = $this->quantityMinimum->getQuantity($item_id);
      $quantity_total += ($quantity_available - $quantity_minimum);

      // Exit if total quantity is already above required quantity.
      if (!is_null($quantity) && $quantity_total > $quantity) {
        break;
      }
    }

    return $quantity_total;
  }

}
