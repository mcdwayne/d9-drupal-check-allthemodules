<?php

namespace Drupal\commerce_inventory\Entity\Storage;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the storage handler class for Commerce Inventory Location entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce Inventory Location entities.
 *
 * @ingroup commerce_inventory
 */
interface InventoryLocationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Check if the ID exists in the database.
   *
   * @param int $id
   *   The entity ID.
   *
   * @return bool
   *   True if the ID exists. False otherwise.
   */
  public function hasId($id);

  /**
   * Find an Inventory Location entity ID by its remote ID.
   *
   * @param string $bundle
   *   The Inventory Location bundle.
   * @param int|string $remote_id
   *   The remote ID to match its related Inventory Location entity Id.
   *
   * @return int|null
   *   The Inventory Location entity ID if found. Null otherwise.
   */
  public function getIdByRemoteId($bundle, $remote_id);

  /**
   * Find Inventory Location entity IDs via their remote ID.
   *
   * @param string $bundle
   *   The Inventory Location bundle.
   * @param string|string[] $remote_ids
   *   The remote IDs to match their related Inventory Location entity Ids.
   *
   * @return array
   *   An array of Inventory Location IDs, keyed by their related remote ID.
   */
  public function getIdsByRemoteIds($bundle, $remote_ids);

}
