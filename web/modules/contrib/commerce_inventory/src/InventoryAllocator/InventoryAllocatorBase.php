<?php

namespace Drupal\commerce_inventory\InventoryAllocator;

use Drupal\commerce_inventory\QuantityManagerInterface;

/**
 * A base class for an inventory allocator.
 */
abstract class InventoryAllocatorBase implements InventoryAllocatorInterface {

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
   * Constructs a new DefaultInventoryPlacementResolver object.
   *
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_available
   *   The quantity available manager.
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_minimum
   *   The quantity minimum manager.
   */
  public function __construct(QuantityManagerInterface $quantity_available, QuantityManagerInterface $quantity_minimum) {
    $this->quantityAvailable = $quantity_available;
    $this->quantityMinimum = $quantity_minimum;
  }

  /**
   * Determines a negative-quantity inventory allocation.
   *
   * @param array $inventory_data
   *   The array of inventory items and optional other information:
   *     - (required) int   $inventory_item_id
   *     - (optional) float $quantity
   *     - (optional) float $previous_quantity.
   * @param float $quantity
   *   The inventory quantity to place.
   * @param bool $manual_allotment
   *   Whether the allotment was specified in a certain way. If TRUE, quantity
   *   and order are specified by passed-in inventory data.
   *
   * @return array
   *   The array of inventory_items and their specified quantity.
   */
  protected function allocateNegativeQuantity(array $inventory_data, $quantity, $manual_allotment = FALSE) {
    $quantity_data = [];
    $quantity_remaining = $quantity;

    foreach ($inventory_data as $item) {
      // Force user-specified allotment for this item.
      if ($manual_allotment && array_key_exists('quantity', $item)) {
        $item_quantity = floatval($item['quantity']);
        $quantity_remaining += $item_quantity;
      }
      elseif (array_key_exists('min_quantity', $item)) {
        // If there is less to return than previously adjusted,
        // since min_quantity is negative.
        // @todo this works but probably needs to be changed to make more sense.
        // @todo should min-quantity be renamed? Should it be positive?
        if ($item['min_quantity'] <= $quantity_remaining) {
          $item_quantity = $quantity_remaining;
          $quantity_remaining = 0;
        }
        // Return what can be returned to this Inventory Item and then go to the
        // next option.
        else {
          $item_quantity = $item['min_quantity'];
          $quantity_remaining -= $item['min_quantity'];
        }
      }
      // Else add quantity back to first Inventory Item option.
      else {
        $item_quantity = $quantity_remaining;
        $quantity_remaining = 0;
      }

      // Add item and its remaining quantity to item data.
      $quantity_data[] = [
        'inventory_item_id' => $item['inventory_item_id'],
        'quantity' => $item_quantity,
      ];

      // Exit if there is no quantity remaining.
      if ($quantity_remaining >= 0) {
        break;
      }
    }

    // Track if there is quantity not currently accounted for.
    if ($quantity_remaining && !empty($quantity_data)) {
      end($quantity_data);
      $quantity_data[key($quantity_data)]['quantity'] -= $quantity_remaining;
    }

    return $quantity_data;
  }

  /**
   * Determines a positive-quantity inventory allocation.
   *
   * @param array $inventory_data
   *   The array of inventory items and optional other information:
   *     - (required) int   $inventory_item_id
   *     - (optional) float $quantity
   *     - (optional) float $previous_quantity.
   * @param float $quantity
   *   The inventory quantity to place.
   * @param bool $manual_allotment
   *   Whether the allotment was specified in a certain way. If TRUE, quantity
   *   and order are specified by passed-in inventory data.
   *
   * @return array
   *   The array of inventory_items and their specified quantity.
   */
  protected function allocatePositiveQuantity(array $inventory_data, $quantity, $manual_allotment = FALSE) {
    $quantity_data = [];
    $quantity_remaining = $quantity;

    // Removing inventory (a positive number).
    foreach ($inventory_data as $item) {
      // This item's available and minimum quantity.
      $quantity_available = $this->quantityAvailable->getQuantity($item['inventory_item_id']);
      $quantity_minimum = $this->quantityMinimum->getQuantity($item['inventory_item_id']);

      // Adjust available-to-minimum quantity ratio to a base of zero.
      $quantity_available = $quantity_available - $quantity_minimum;

      // Exclude previous quantity.
      if (array_key_exists('previous_quantity', $item)) {
        $quantity_available = $quantity_available + floatval($item['previous_quantity']);
      }

      // Force user-specified allotment for this item.
      if ($manual_allotment && array_key_exists('quantity', $item)) {
        $item_quantity = floatval($item['quantity']);
        $quantity_remaining -= $item_quantity;
      }
      // Item has enough quantity available.
      elseif ($quantity_available >= $quantity_remaining) {
        $item_quantity = $quantity_remaining;
        $quantity_remaining = 0;
      }
      // This item doesn't have enough quantity available. Remove as much as
      // possible and let the next item handle remainder.
      else {
        $item_quantity = $quantity_available;
        $quantity_remaining -= $quantity_available;
      }

      // Add item and its remaining quantity to item data.
      $quantity_data[] = [
        'inventory_item_id' => $item['inventory_item_id'],
        'quantity' => $item_quantity,
      ];

      // Exit if there is no quantity remaining.
      if ($quantity_remaining <= 0) {
        break;
      }
    }

    // Track if there is quantity not currently accounted for.
    if ($quantity_remaining && !empty($quantity_data)) {
      end($quantity_data);
      $quantity_data[key($quantity_data)]['quantity'] += $quantity_remaining;
    }

    // Remove any quantity that is 0 (beginning values with no inventory).
    $quantity_data = array_filter($quantity_data, function ($value) {
      return ($value['quantity'] <> 0);
    });

    return $quantity_data;
  }

  /**
   * Determines inventory placement.
   *
   * @param array $inventory_data
   *   The array of inventory items and optional other information:
   *     - (required) int   $inventory_item_id
   *     - (optional) float $quantity
   *     - (optional) float $previous_quantity.
   * @param float $quantity
   *   The inventory quantity to place.
   * @param bool $manual_allotment
   *   Whether the allotment was specified in a certain way. If TRUE, quantity
   *   and order are specified by passed-in inventory data.
   *
   * @return array
   *   The array of inventory_items and their specified quantity.
   */
  protected function getInventoryAllocation(array $inventory_data, $quantity, $manual_allotment = FALSE) {
    // Returning inventory (a negative number).
    if ($quantity < 0) {
      return $this->allocateNegativeQuantity($inventory_data, $quantity, $manual_allotment);
    }

    // Removing inventory (a positive number).
    return $this->allocatePositiveQuantity($inventory_data, $quantity, $manual_allotment);
  }

}
