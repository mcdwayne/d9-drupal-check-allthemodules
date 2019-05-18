<?php

namespace Drupal\commerce_inventory;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a manager for determining on-hand quantity of Inventory Items.
 */
class QuantityOnHandManager implements QuantityManagerInterface {

  /**
   * The Inventory Adjustment storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface
   */
  protected $adjustmentStorage;

  /**
   * The Commerce Inventory cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The entity type manager;.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new QuantityOnHandManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Commerce Inventory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(CacheBackendInterface $cache_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->cacheFactory = $cache_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * The Inventory Adjustment storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface
   *   The Inventory Adjustment storage instance.
   */
  protected function getAdjustmentStorage() {
    if (is_null($this->adjustmentStorage)) {
      $this->adjustmentStorage = $this->entityTypeManager->getStorage('commerce_inventory_adjustment');
    }
    return $this->adjustmentStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity($inventory_item_id) {
    $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'on_hand');

    // Load on-hand quantity if valid.
    if ($cache = $this->cacheFactory->get($cid)) {
      $cache_data = $cache->data;
    }
    else {
      // Calculate and set on-hand quantity.
      $quantity = $this->getAdjustmentStorage()->calculateQuantity($inventory_item_id);
      $cache_data['quantity'] = $quantity;
      $cache_tags = InventoryHelper::generateQuantityCacheTags($inventory_item_id);
      $this->cacheFactory->set($cid, $cache_data, Cache::PERMANENT, Cache::mergeTags($cache_tags, [$cid]));
    }

    // Return quantity.
    return floatval($cache_data['quantity']);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateQuantity($inventory_item_id) {
    // Invalidate all quantity cache for this item.
    $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id);
    $this->cacheFactory->invalidate($cid);
  }

}
