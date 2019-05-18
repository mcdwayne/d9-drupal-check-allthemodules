<?php

namespace Drupal\commerce_inventory_order\Guard;

use Drupal\commerce_inventory_order\InventoryOrderManager;
use Drupal\commerce_inventory_store\InventoryStoreManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\Core\Entity\EntityInterface;

/**
 * A Commerce Order state-machine Guard to check that proper Inventory exists.
 */
class OrderGuard implements GuardInterface {

  /**
   * The Commerce Inventory cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The inventory Commerce Order manager.
   *
   * @var \Drupal\commerce_inventory_order\InventoryOrderManager
   */
  protected $inventoryOrderManager;

  /**
   * The inventory Commerce Store manager.
   *
   * @var \Drupal\commerce_inventory_store\InventoryStoreManager
   */
  protected $inventoryStoreManager;

  /**
   * Constructs a new StoreInventoryPlacementResolver object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Commerce Inventory cache backend.
   * @param \Drupal\commerce_inventory_order\InventoryOrderManager $inventory_order_manager
   *   The inventory Commerce Order manager.
   * @param \Drupal\commerce_inventory_store\InventoryStoreManager $inventory_store_manager
   *   The inventory Commerce Store manager.
   */
  public function __construct(CacheBackendInterface $cache_factory, InventoryOrderManager $inventory_order_manager, InventoryStoreManager $inventory_store_manager) {
    $this->cacheFactory = $cache_factory;
    $this->inventoryOrderManager = $inventory_order_manager;
    $this->inventoryStoreManager = $inventory_store_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */

    // Always allow canceling and placing orders.
    if (($transition->getId() == 'cancel' || $transition->getId() == 'place')) {
      return TRUE;
    }

    // Create CID.
    $cid = implode(':', [
      'order_workflow_transition_guard',
      $entity->id(),
      $transition->getId(),
    ]);

    // Check if this is in cache.
    if (FALSE && $cache = $this->cacheFactory->get($cid)) {
      $data = $cache->data;
    }
    else {
      // Allow by default.
      $allowed = TRUE;
      $disallowed_items = [];

      // Initiate related cache tags.
      $cache_tags = $entity->getStore()->getCacheTagsToInvalidate();
      $cache_tags = Cache::mergeTags($cache_tags, $entity->getCacheTagsToInvalidate());
      $cache_tags = Cache::mergeTags($cache_tags, ['commerce_order_item_type_list']);

      // Load workflow transitions, keyed by Order workflow transition, then by
      // Order Item workflow transition.
      $transitions = $this->inventoryOrderManager->getAllBundleInventoryWorkflowTransitions();

      // Check if transition is tracked for any order item bundle.
      if (array_key_exists($transition->getId(), $transitions) && !empty($transitions[$transition->getId()])) {
        // Run through each Order Item to make sure they can be properly placed
        // in inventory.
        foreach ($entity->getItems() as $item) {
          // This Order Item bundle is transitioned on this Order Transition.
          if (array_key_exists($item->bundle(), $transitions[$transition->getId()])) {
            // Invalidate cache if Order Item or purchasable entity is updated
            // or removed.
            $cache_tags = Cache::mergeTags($cache_tags, $item->getCacheTagsToInvalidate());
            $cache_tags = Cache::mergeTags($cache_tags, $item->getPurchasedEntity()->getCacheTagsToInvalidate());

            // Load applicable inventory items and locations for this store and
            // purchasable entity pair.
            $inventory_item_locations = $this->inventoryStoreManager->getStoreItemLocations($item->getPurchasedEntity(), $entity->getStore());

            // Track inventory cache tags to invalidate cache if they are
            // not accessible anymore.
            if (!empty($inventory_item_locations)) {
              $inventory_cache_tags = [];
              foreach ($inventory_item_locations as $item_location) {
                $inventory_cache_tags[] = 'commerce_inventory_item:' . $item_location['item_id'];
                $inventory_cache_tags[] = 'commerce_inventory_location:' . $item_location['location_id'];
              }
              $cache_tags = Cache::mergeTags($cache_tags, $inventory_cache_tags);
            }
            // Disallow since there are no available Inventory Locations and
            // track Order Items for user message.
            else {
              $allowed = FALSE;
              $disallowed_items[] = [
                'label' => $item->getPurchasedEntity()->label(),
                'id' => $item->getPurchasedEntity()->id(),
              ];
            }
          }
        }
      }

      // Set cache data.
      $data['allowed'] = $allowed;
      $data['order_override'] = $entity->getData('inventory_guard_override', FALSE);
      $data['items'] = $disallowed_items;
      $this->cacheFactory->set($cid, $data, Cache::PERMANENT, $cache_tags);
    }

    // Alert user why the order can't be transitioned.
    if ($data['allowed'] !== TRUE) {
      $message_type = ($data['order_override']) ? 'warning' : 'error';
      $message_replacements = [
        '@items' => implode(', ', array_column($data['items'], 'label')),
        '@transition' => strtolower($transition->getLabel()),
      ];
      drupal_set_message(new TranslatableMarkup("The following order items have no available inventory: <strong>@items</strong>. <em>This could be caused from Inventory or Inventory Locations becoming deactivated, or by removing a Store's accessible Inventory Locations.</em>", $message_replacements), $message_type);

      if ($data['order_override']) {
        drupal_set_message(new TranslatableMarkup('Restriction to <strong>@transition</strong> overridden. Unavailable inventory will not be tracked.', $message_replacements), $message_type);
        $data['allowed'] = TRUE;
      }
      else {
        drupal_set_message(new TranslatableMarkup('Please fix inventory availability or remove the affected order items to <strong>@transition.</strong>', $message_replacements), $message_type);
      }
    }

    return $data['allowed'];
  }

}
