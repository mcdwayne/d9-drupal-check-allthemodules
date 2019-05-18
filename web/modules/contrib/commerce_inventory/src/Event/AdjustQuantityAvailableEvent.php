<?php

namespace Drupal\commerce_inventory\Event;

use Drupal\Core\Cache\Cache;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the Inventory Item quantity-available event.
 *
 * @see \Drupal\commerce_inventory\Event\CommerceInventoryEvents
 */
class AdjustQuantityAvailableEvent extends Event {

  /**
   * The cache tags to add to the adjustment cache.
   *
   * @var array
   */
  protected $cacheTags = [];

  /**
   * The Inventory item entity ID to check modify quantity.
   *
   * @var int
   */
  protected $inventoryItemId;

  /**
   * The Inventory item entity to check modify quantity.
   *
   * @var \Drupal\commerce_inventory\Entity\InventoryItemInterface
   */
  protected $inventoryItem;

  /**
   * Array of quantity adjustments made by each dispatched subscriber.
   *
   * @var array
   */
  protected $quantityAdjustments = [];

  /**
   * The current quantity on-hand of passed-in Inventory Item.
   *
   * @var float
   */
  protected $quantityOnHand;

  /**
   * Constructs a QuantityAvailableEvent object.
   *
   * @param int|string $inventory_item_id
   *   The Inventory Item entity Id.
   * @param float $quantity_on_hand
   *   The current quantity on-hand of passed-in Inventory Item.
   */
  public function __construct($inventory_item_id, $quantity_on_hand) {
    $this->inventoryItemId = intval($inventory_item_id);
    $this->quantityOnHand = $quantity_on_hand;
  }

  /**
   * Returns an array of cache tags related to the adjustment.
   *
   * @return array
   *   The array of cache tags.
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * Returns the Inventory Item entity Id.
   *
   * @return int
   *   The Inventory Item entity ID
   */
  public function getInventoryItemId() {
    return $this->inventoryItemId;
  }

  /**
   * Returns the Inventory Item entity.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryItemInterface
   *   The Inventory Item entity.
   */
  public function getInventoryItem() {
    if (is_null($this->inventoryItem)) {
      $this->inventoryItem = \Drupal::entityTypeManager()->getStorage('commerce_inventory_item')->load($this->getInventoryItemId());
    }
    return $this->inventoryItem;
  }

  /**
   * Add a quantity adjustment.
   *
   * @param string $adjustment_id
   *   The ID used to track the adjustment. Can be used to allow for
   *   re-adjustments by the same subscriber or finding quantity adjustments
   *   without a certain subscriber.
   * @param float $adjustment_quantity
   *   A negative or positive adjustment of the current quantity on-hand.
   * @param array $adjustment_dependencies
   *   An array of entity IDs, which itself is keyed by entity type id.
   *   Example: ['entity_type_id_here' => [1, 2, 4]].
   * @param string[] $additional_cache_tags
   *   An array of additional cache tags to add to the quantity adjustment.
   *   Dependent-entity cache tags are automatically added.
   */
  public function addQuantityAdjustment($adjustment_id, $adjustment_quantity, array $adjustment_dependencies, array $additional_cache_tags = []) {
    $cache_tags = [];
    $dependencies = [];

    // Validate and clean dependencies. Add dependencies to cache tags.
    foreach ($adjustment_dependencies as $dependency_entity_type => $dependency_ids) {
      // Support if single ID is passed in instead of an array of IDs.
      if (is_string($dependency_ids) || is_int($dependency_ids)) {
        $dependencies[$dependency_entity_type][] = $dependency_ids;
        $cache_tags[] = $dependency_entity_type . ':' . $dependency_ids;
      }
      elseif (is_array($dependency_ids)) {
        foreach ($dependency_ids as $dependency_id) {
          if (is_string($dependency_id) || is_int($dependency_id)) {
            $dependencies[$dependency_entity_type][] = $dependency_id;
            $cache_tags[] = $dependency_entity_type . ':' . $dependency_id;
          }
        }
      }
    }

    $this->quantityAdjustments[$adjustment_id] = [
      'dependencies' => $dependencies,
      'quantity' => $adjustment_quantity,
      'cache_tags' => $additional_cache_tags,
    ];

    $this->cacheTags = Cache::mergeTags($this->cacheTags, array_unique($cache_tags));
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $additional_cache_tags);
  }

  /**
   * Return the quantity adjustments made so-far.
   *
   * @return array
   *   The quantity adjustments made so-far.
   */
  public function getQuantityAdjustments() {
    return $this->quantityAdjustments;
  }

  /**
   * Return how much the quantity on-hand should be adjusted.
   *
   * @return float
   *   The total quantity adjustment.
   */
  public function getQuantityAdjustment() {
    return array_sum(array_column($this->quantityAdjustments, 'quantity'));
  }

  /**
   * Returns the current Inventory Item's quantity on-hand.
   *
   * @return float
   *   The current Inventory Item's quantity on-hand.
   */
  public function getQuantityOnHand() {
    return $this->quantityOnHand;
  }

  /**
   * Returns the current Inventory Item's quantity available.
   *
   * @return float|int
   *   The current Inventory Item's quantity available.
   */
  public function getQuantityAvailable() {
    $adjustment = $this->getQuantityAdjustment();
    return array_sum([$this->quantityOnHand, $this->getQuantityAdjustment()]);
  }

}
