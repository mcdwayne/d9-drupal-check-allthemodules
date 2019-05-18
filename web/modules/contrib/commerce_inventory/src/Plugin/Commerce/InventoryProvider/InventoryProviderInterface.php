<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider;

use Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface;
use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\entity\BundlePlugin\BundlePluginInterface;

/**
 * Defines an interface for Inventory Provider plugins.
 */
interface InventoryProviderInterface extends BundlePluginInterface, ContextAwarePluginInterface {

  /**
   * Gets the Inventory Provider label.
   *
   * @return string
   *   The Inventory Provider label.
   */
  public function label();

  /**
   * Acts on an adjustment before the presave hook is invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $adjustment
   *   The Inventory Adjustment to act on.
   */
  public function onAdjustmentPreSave(InventoryAdjustmentInterface $adjustment);

  /**
   * Acts on an adjustment after the postsave hook is invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $adjustment
   *   The Inventory Adjustment to act on.
   */
  public function onAdjustmentPostSave(InventoryAdjustmentInterface $adjustment);

  /**
   * Whether Inventory Items need additional configuration on creation.
   *
   * @return bool
   *   True if addition configuration is needed. False otherwise.
   */
  public function isItemConfigurationRequired();

  /**
   * Returns whether an Inventory Item is valid for this type.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item to validate.
   *
   * @return bool
   *   True of the Inventory item is valid. False otherwise.
   */
  public function validateItemConfiguration(InventoryItemInterface $inventory_item);

  /**
   * Acts on a created entity before hooks are invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item to act on.
   */
  public function onItemPostCreate(InventoryItemInterface $inventory_item);

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item to act on.
   */
  public function onItemPreSave(InventoryItemInterface $inventory_item);

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item to act on.
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   */
  public function onItemPostSave(InventoryItemInterface $inventory_item, $update = TRUE);

  /**
   * Whether Inventory Items are required to have a remote ID .
   *
   * @return bool
   *   True of the Inventory Item remote ID is required. False otherwise.
   */
  public function isItemRemoteIdRequired();

  /**
   * Gets the current quantity of the Inventory Item's remote object.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The managed Inventory item.
   *
   * @return float|null
   *   Quantity if found, otherwise NULL.
   */
  public function getItemRemoteQuantity(InventoryItemInterface $inventory_item);

  /**
   * Whether Inventory Locations are required to have a remote ID .
   *
   * @return bool
   *   True of the Inventory Location remote ID is required. False otherwise.
   */
  public function isLocationRemoteIdRequired();

  /**
   * Acts on a created entity before hooks are invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryLocationInterface $inventory_location
   *   The Inventory Location to act on.
   */
  public function onLocationPostCreate(InventoryLocationInterface $inventory_location);

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryLocationInterface $inventory_location
   *   The Inventory Location to act on.
   */
  public function onLocationPreSave(InventoryLocationInterface $inventory_location);

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryLocationInterface $inventory_location
   *   The Inventory Location to act on.
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   */
  public function onLocationPostSave(InventoryLocationInterface $inventory_location, $update = TRUE);

  /**
   * Adjust the provider-tracked quantity count of an Inventory Item.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item entity.
   * @param float $quantity
   *   The amount to either increase or decrease the provider-tracked quantity.
   *
   * @return bool
   *   A boolean indicating whether the sync completed successfully.
   */
  public function adjustProviderQuantity(InventoryItemInterface $inventory_item, $quantity);

  /**
   * Get provider's quantity for the Inventory Item.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item entity.
   *
   * @return float|null
   *   The Inventory Item's quantity from the provider. Null if check failed.
   */
  public function getProviderQuantity(InventoryItemInterface $inventory_item);

  /**
   * Sync the local and provider inventory count of an Inventory Item entity.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item entity.
   * @param bool $update_provider_from_local
   *   Whether the sync should update the provider count from local, or the
   *   local count from the provider.
   *
   * @return bool
   *   A boolean indicating whether the sync completed successfully.
   */
  public function syncProviderQuantity(InventoryItemInterface $inventory_item, $update_provider_from_local = TRUE);

  /**
   * Return a key-value list of Entity remote-ID options.
   *
   * @param string $q
   *   The search query to limit the results.
   * @param string $entity_type_id
   *   The entity type id to search for relevant remote ids.
   * @param array $contexts
   *   An associative array containing the entity object.
   *
   * @return array
   *   A key-value list of remote-ID options.
   */
  public function getRemoteIdOptions($q, $entity_type_id, array $contexts = []);

  /**
   * Check whether a remote ID is valid.
   *
   * @param string $value
   *   The remote ID to validate.
   * @param string $entity_type_id
   *   The entity type id to search for relevant remote ids.
   * @param array $contexts
   *   An associative array containing the entity object.
   *
   * @return bool
   *   True if the remote ID is valid. False otherwise.
   */
  public function validateRemoteId($value, $entity_type_id, array $contexts = []);

  /**
   * Alter the bundle field definitions for the entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for
   *   multiple, possibly dynamic entity types.
   * @param string $bundle
   *   The bundle.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $base_field_definitions
   *   The list of base field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function bundleFieldDefinitionsAlter(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions);

}
