<?php

namespace Drupal\commerce_inventory\Entity\Storage;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the storage handler class for Commerce Inventory Item entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce Inventory Item entities.
 *
 * @ingroup commerce_inventory
 */
interface InventoryItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Return an entity query for Inventory Item entities.
   *
   * @param int|null $location_id
   *   An Inventory Location entity ID.
   * @param null|string $purchasable_entity_type_id
   *   A Purchasable Entity entity-type ID.
   * @param array|int|null|string $purchasable_entity_id
   *   A Purchasable Entity entity ID.
   * @param bool|null $status
   *   Boolean stating whether the query is for active or inactive items. NULL
   *   for all items.
   * @param array $properties
   *   Properties array used for the entity query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query.
   */
  public function getItemQuery($location_id = NULL, $purchasable_entity_type_id = NULL, $purchasable_entity_id = NULL, $status = NULL, array $properties = []);

  /**
   * Get an Inventory Item ID.
   *
   * @param int $location_id
   *   The Inventory Location entity ID.
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param int|string $purchasable_entity_id
   *   The Purchasable Entity entity ID.
   *
   * @return int|null
   *   Return the ID of the Inventory Item entity. NULL if not found.
   */
  public function getItemId($location_id, $purchasable_entity_type_id, $purchasable_entity_id);

  /**
   * Get Inventory Item IDs.
   *
   * @param int[] $location_ids
   *   The Inventory Location entity IDs.
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param int|string $purchasable_entity_id
   *   The Purchasable Entity entity ID.
   *
   * @return int|null
   *   Return the Inventory Item entity IDs. NULL if not found.
   */
  public function getItemIds(array $location_ids, $purchasable_entity_type_id, $purchasable_entity_id);

  /**
   * Gets Inventory Item IDs for the specified Inventory Location.
   *
   * @param int $location_id
   *   The Inventory Location entity ID.
   * @param bool|null $status
   *   Boolean stating whether the query is for active or inactive items. NULL
   *   for all items.
   *
   * @return array
   *   An array of Inventory Item entity IDs.
   */
  public function getItemIdsByLocation($location_id, $status = NULL);

  /**
   * Gets Inventory Item IDs for the specified purchasable entity.
   *
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param array|int|null|string $purchasable_entity_id
   *   The Purchasable Entity entity ID.
   * @param bool|null $status
   *   Boolean stating whether the query is for active or inactive items. NULL
   *   for all items.
   *
   * @return array
   *   An array of Inventory Item entity IDs.
   */
  public function getItemIdsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id = NULL, $status = NULL);

  /**
   * Find Inventory Item entity IDs via their remote ID.
   *
   * @param string $bundle
   *   The Inventory Item bundle.
   * @param int|string $location_remote_id
   *   The location remote ID.
   * @param string|string[] $remote_ids
   *   The remote IDs to match their related Inventory Item entity Ids.
   *
   * @return array
   *   An array of Inventory Item IDs, keyed by their related remote ID.
   */
  public function getItemIdsByRemoteIds($bundle, $location_remote_id, $remote_ids);

  /**
   * Get an Inventory Item's related Inventory Location IDs.
   *
   * @param int[] $item_ids
   *   An array of Inventory Item entity IDs.
   *
   * @return int[]
   *   An array of Inventory Location entity IDs keyed by their respective
   *   Inventory Item entity IDs.
   */
  public function getLocationIds(array $item_ids);

  /**
   * Get Inventory Location IDs that hold the specified Purchasable Entity.
   *
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param int|string $purchasable_entity_id
   *   The Purchasable Entity entity ID.
   * @param bool|null $status
   *   Boolean stating whether the query is for active or inactive items. NULL
   *   for all items.
   *
   * @return array
   *   An array of Inventory Item IDs.
   */
  public function getLocationIdsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id, $status = NULL);

  /**
   * Get purchasable entity IDs of an entity-type at an Inventory Location.
   *
   * @param int $location_id
   *   The Inventory Location entity ID.
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   *
   * @return array
   *   An array of Purchasable Entity entity IDs.
   */
  public function getPurchasableEntityIds($location_id, $purchasable_entity_type_id);

  /**
   * Load Inventory Item using a purchasable entity and Inventory Location.
   *
   * @param int $location_id
   *   The Inventory Location entity ID.
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param int|string $purchasable_entity_id
   *   The Purchasable Entity entity ID.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryItemInterface|null
   *   The Inventory Item entity if found. Null otherwise.
   */
  public function loadItem($location_id, $purchasable_entity_type_id, $purchasable_entity_id);

  /**
   * Gets Inventory Item entities for the specified Inventory Location.
   *
   * @param int $location_id
   *   The Inventory Location entity ID.
   * @param bool|null $status
   *   Boolean stating whether the query is for active or inactive items. NULL
   *   for all items.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryItemInterface[]
   *   An array of Inventory Item entities.
   */
  public function loadItemsByLocation($location_id, $status = NULL);

  /**
   * Loads Inventory Item entities for the specified purchasable entity.
   *
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param array|int|null|string $purchasable_entity_id
   *   The Purchasable Entity entity ID.
   * @param bool|null $status
   *   Boolean stating whether the query is for active or inactive items. NULL
   *   for all items.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryItemInterface[]
   *   An array of Inventory Item entities.
   */
  public function loadItemsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id = NULL, $status = NULL);

  /**
   * Creates Inventory Item from an Inventory Location and purchasable entity.
   *
   * @param int|\Drupal\commerce_inventory\Entity\InventoryLocationInterface $location
   *   Either the location or it's ID.
   * @param string $purchasable_entity_type_id
   *   The Purchasable Entity entity-type ID.
   * @param int[]|string[] $purchasable_entity_ids
   *   An array of Purchasable Entity entity IDs.
   * @param array $values
   *   (optional) An associative array of initial field values keyed by field
   *   name. If none is provided default values will be applied.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryItemInterface[]
   *   The created inventory items.
   */
  public function createMultiple($location, $purchasable_entity_type_id, array $purchasable_entity_ids, array $values = []);

  /**
   * Sync Inventory Item quantity from the provider, updating the local count.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface[] $inventory_items
   *   The Inventory Items to sync.
   */
  public function syncQuantityFromProvider(array $inventory_items);

  /**
   * Sync Inventory Item quantity to the provider, updating the provider count.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface[] $inventory_items
   *   The Inventory Items to sync.
   */
  public function syncQuantityToProvider(array $inventory_items);

}
