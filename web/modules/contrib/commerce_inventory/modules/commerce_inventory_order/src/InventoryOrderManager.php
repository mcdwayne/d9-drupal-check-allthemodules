<?php

namespace Drupal\commerce_inventory_order;

use Drupal\commerce_inventory\InventoryAllocationManager;
use Drupal\commerce_inventory\InventoryHelper;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\OrderItemTypeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an inventory manager for Order Item entities.
 */
class InventoryOrderManager {

  /**
   * The Commerce Inventory cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The chain inventory placement resolver.
   *
   * @var \Drupal\commerce_inventory\InventoryAllocationManager
   */
  protected $inventoryAllocation;

  /**
   * The Inventory Adjustment entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface
   */
  protected $inventoryAdjustmentStorage;

  /**
   * The Inventory Item entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   */
  protected $inventoryItemStorage;

  /**
   * The Order Item entity storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The OrderType entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * Constructs a new OrderInventoryManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Commerce Inventory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\commerce_inventory\InventoryAllocationManager $allocation_manager
   *   The inventory allocation manager.
   */
  public function __construct(CacheBackendInterface $cache_factory, EntityTypeManagerInterface $entity_type_manager, InventoryAllocationManager $allocation_manager) {
    $this->cacheFactory = $cache_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->inventoryAdjustmentStorage = $entity_type_manager->getStorage('commerce_inventory_adjustment');
    $this->inventoryAllocation = $allocation_manager;
    $this->inventoryItemStorage = $entity_type_manager->getStorage('commerce_inventory_item');
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
  }

  /**
   * Returns an the Order's selected adjustment workflow ID.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemTypeInterface|string $order_item_type
   *   The Order Type ID.
   *
   * @return string
   *   The adjustment workflow ID.
   */
  public static function getBundleInventoryWorkflowId($order_item_type) {
    // Load order type if bundle id passed in.
    if (is_string($order_item_type)) {
      $order_item_type = OrderItemType::load($order_item_type);
    }

    // Return Order Type's setting.
    if ($order_item_type instanceof OrderItemTypeInterface) {
      return $order_item_type->getThirdPartySetting('commerce_inventory_order', 'inventory_workflow', 'default');
    }

    // Default to the 'default' id.
    return 'default';
  }

  /**
   * Returns an the Order's selected adjustment workflow ID.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemTypeInterface|string $order_item_type
   *   The Order Type ID.
   *
   * @return array
   *   The inventory workflow transitions.
   */
  public static function getBundleInventoryWorkflowTransitions($order_item_type) {
    // Load order type if bundle id passed in.
    if (is_string($order_item_type)) {
      $order_item_type = OrderItemType::load($order_item_type);
    }

    // Return Order Type's setting.
    if ($order_item_type instanceof OrderItemTypeInterface) {
      return $order_item_type->getThirdPartySetting('commerce_inventory_order', 'inventory_workflow_transitions', []);
    }

    // Default to an empty array.
    return [];
  }

  /**
   * Returns all Order-to-Order-Item workflow transition relationships.
   *
   * @return array
   *   An array of relationships, keyed by bundle ID.
   */
  public function getAllBundleInventoryWorkflowTransitions() {
    $cid = 'inventory_workflow_transitions:order_item_type';

    // Return cached bundle transition information.
    if ($cache = $this->cacheFactory->get($cid)) {
      $data = $cache->data;
    }
    // Compile all workflow transitions for each Order Item bundle.
    else {
      /** @var \Drupal\state_machine\WorkflowManagerInterface $workflow_manager */
      $workflow_manager = \Drupal::service('plugin.manager.workflow');

      $item_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');

      /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface[] $item_bundles */
      $item_bundles = $item_storage->loadMultiple();

      // Setup cache data and tags.
      $data = [];
      $cache_tags = $item_storage->getEntityType()->getListCacheTags();

      // Get the Order Item bundles transitions.
      $item_transitions = [];
      foreach ($item_bundles as $item_bundle_id => $item_bundle) {
        $item_transitions[$item_bundle_id] = self::getBundleInventoryWorkflowTransitions($item_bundle);
      }

      // Populate the workflow state and transitions.
      foreach ($item_transitions as $item_bundle_id => $item_bundle_transitions) {
        $item_bundle_worklow_id = InventoryOrderManager::getBundleInventoryWorkflowId($item_bundle_id);
        /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $item_bundle_worklow */
        $item_bundle_worklow = $workflow_manager->createInstance($item_bundle_worklow_id);

        // Key data by Order workflow transition ID, then by Order Item bundle.
        foreach ($item_bundle_transitions as $order_transition_id => $item_transition_id) {
          if ($item_transition = $item_bundle_worklow->getTransition($item_transition_id)) {
            $data[$order_transition_id][$item_bundle_id] = [
              'state' => $item_transition->getToState()->getId(),
              'transition' => $item_transition_id,
            ];
          }
        }
      }

      // Set cache.
      $this->cacheFactory->set($cid, $data, Cache::PERMANENT, $cache_tags);
    }

    return $data;
  }

  /**
   * If this Order Item's inventory adjustments are allotted manually.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   *
   * @return bool
   *   True if the adjustments are allotted manually. False otherwise.
   */
  public static function isAdjustedManually(OrderItemInterface $order_item) {
    if ($order_item->get('inventory_adjustment_manual')->isEmpty()) {
      return FALSE;
    }
    return $order_item->get('inventory_adjustment_manual')->get(0)->getValue()['value'] == 1;
  }

  /**
   * Gets an Order Item's adjustment state ID.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   *
   * @return string
   *   The state ID.
   */
  public static function getAdjustmentStateId(OrderItemInterface $order_item) {
    if ($order_item->get('inventory_adjustment_state')->isEmpty()) {
      return 'untracked';
    }
    return $order_item->get('inventory_adjustment_state')->get(0)->getValue()['value'];
  }

  /**
   * Checks whether an Order Item is at a certain adjustment state.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   * @param string $adjustment_state
   *   The adjustment state.
   *
   * @return bool
   *   Whether the Order is at a certain adjustment state.
   */
  public static function checkAdjustmentState(OrderItemInterface $order_item, $adjustment_state) {
    if ($order_item->get('inventory_adjustment_state')->isEmpty()) {
      return FALSE;
    }
    return ($order_item->get('inventory_adjustment_state')->get(0)->getValue()['value'] == $adjustment_state);
  }

  /**
   * Return created Inventory Adjustment quantities by Order Item.
   *
   * @param int $order_item_id
   *   The Order Item ID.
   * @param bool $consolidate_by_item
   *   Whether the adjustments should be consolidated by Inventory Item ID.
   *
   * @return array
   *   The on-hand adjustments.
   */
  public function getAdjustments($order_item_id, $consolidate_by_item = TRUE) {
    // Pull previous on-hand inventory adjustments.
    $adjustment_query = $this->inventoryAdjustmentStorage->getQuantitySelectQuery();
    $adjustment_query->condition('order_item_id', $order_item_id);

    // Consolidate quantity field.
    if ($consolidate_by_item) {
      // Remove quantity field since it's going to be generated.
      unset($adjustment_query->getFields()['quantity']);
      $adjustment_query->addExpression('sum(quantity)', 'quantity');
      $adjustment_query->groupBy('inventory_item_id');
    }

    return $adjustment_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Apply the Order Item inventory workflow transition.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   * @param string $transition_id
   *   The transition ID.
   *
   * @return bool
   *   Whether the transition was applied.
   */
  public static function transitionAdjustmentState(OrderItemInterface $order_item, $transition_id) {
    $state_field = $order_item->get('inventory_adjustment_state');

    // Apply default value if not set.
    if ($state_field->isEmpty()) {
      $state_field->applyDefaultValue();
    }

    /** @var \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface $state */
    $state = $state_field->first();
    $transitions = $state->getTransitions();

    // Apply transition if it can be applied.
    if (array_key_exists($transition_id, $transitions)) {
      $state->applyTransition($transitions[$transition_id]);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Adjusts full on-hand Inventory count based on deleted Order Items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   * @param bool $save_adjustments
   *   Whether to save the adjustments before returning them.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface[]
   *   The created Inventory Adjustments.
   */
  public function deleteInventory(OrderItemInterface $order_item, $save_adjustments = TRUE) {
    // Load previous adjustments.
    $adjustments = $this->getAdjustments($order_item->id());

    // Exit early if adjustments haven't been made.
    if (empty($adjustments)) {
      return [];
    }

    // Load Inventory Items.
    $inventory_item_ids = array_column($adjustments, 'inventory_item_id');
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface[] $inventory_items */
    $inventory_items = $this->inventoryItemStorage->loadMultiple($inventory_item_ids);

    // Create reusable values.
    // @todo might want to make $adjustment_type_id dynamic based on adjustment quantity
    $adjustment_type_id = 'return';
    $adjustment_values = [
      'order_id' => $order_item->getOrderId(),
      'user_id' => $order_item->getOrder()->getCustomerId(),
    ];

    // Convert each item to a new adjustment.
    $inventory_adjustments = [];
    foreach ($adjustments as $adjustment) {
      if (array_key_exists($adjustment['inventory_item_id'], $inventory_items) && $adjustment['quantity'] <> 0) {
        $inventory_adjustments[] = $this->inventoryAdjustmentStorage->createAdjustment($adjustment_type_id, $inventory_items[$adjustment['inventory_item_id']], $adjustment['quantity'], $adjustment_values, NULL, $save_adjustments);
      }
    }

    return $inventory_adjustments;
  }

  /**
   * Cancels held inventory placeholders.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   */
  public function cancelInventoryHolds(OrderItemInterface $order_item) {
    // Invalidate the previously used item cache tags.
    if ($order_item->get('inventory_adjustment_holds')->isEmpty() == FALSE) {
      foreach (array_column($order_item->get('inventory_adjustment_holds')->getValue(), 'target_id') as $inventory_item_id) {
        $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'available');
        $this->cacheFactory->invalidate($cid);
      }
    }

    // Clear adjustment placeholders and settings.
    $order_item->set('inventory_adjustment_holds', []);
    $order_item->set('inventory_adjustment_manual', FALSE);
  }

  /**
   * Adjusts full on-hand Inventory count based on updated Order Items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   * @param bool $save_adjustments
   *   Whether to save the adjustments before returning them.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface[]
   *   The created Inventory Adjustments.
   */
  public function convertInventoryHolds(OrderItemInterface $order_item, $save_adjustments = TRUE) {
    // Exit early if order isn't set.
    if (is_null($order_item->getOrderId())) {
      return [];
    }

    // Initialize return.
    $inventory_adjustments = [];

    // Get previous inventory adjustments.
    $adjustments_previous = $this->getAdjustments($order_item->id());

    // Get relative quantity from previous full adjustments.
    $quantity = floatval($order_item->getQuantity());
    $quantity_previous = array_sum(array_column($adjustments_previous, 'quantity'));
    $quantity_relative = $quantity + $quantity_previous;

    // If quantity has changed.
    if ($quantity_relative <> 0) {
      // Get current adjustment holds to convert to on-hand adjustments.
      $holds = $order_item->get('inventory_adjustment_holds')->getValue();
      $inventory_item_ids = array_column($holds, 'target_id');

      // Load Inventory Items.
      /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface[] $inventory_items */
      $inventory_items = $this->inventoryItemStorage->loadMultiple($inventory_item_ids);

      // Create reusable values.
      $adjustment_type_id = ($quantity_relative > 0) ? 'sell' : 'return';
      $adjustment_values = [
        'order_id' => $order_item->getOrderId(),
        'order_item_id' => $order_item->id(),
        'user_id' => $order_item->getOrder()->getCustomerId(),
      ];

      // Convert each item to a new adjustment.
      foreach ($holds as $adjustment) {
        $item_id = $adjustment['target_id'];
        $item_quantity = $adjustment['quantity'];
        if (array_key_exists($item_id, $inventory_items) && $item_quantity <> 0) {
          $inventory_adjustments[] = $this->inventoryAdjustmentStorage->createAdjustment($adjustment_type_id, $inventory_items[$item_id], $item_quantity, $adjustment_values, NULL, $save_adjustments);
        }
      }

      // Invalidate items.
      $cids = [];
      foreach ($inventory_item_ids as $inventory_item_id) {
        $cids[] = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'available');
        $cids[] = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'on_hand');
      }
      $this->cacheFactory->invalidateMultiple($cids);
    }

    // Clear adjustment-related settings.
    $order_item->set('inventory_adjustment_holds', []);
    $order_item->set('inventory_adjustment_manual', FALSE);

    // Default to an empty array.
    return $inventory_adjustments;
  }

  /**
   * Puts available inventory on hold for an Order Item entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Order Item entity.
   */
  public function makeInventoryHolds(OrderItemInterface $order_item) {
    // Get previous inventory adjustments.
    $adjustments_previous = $this->getAdjustments($order_item->id());

    // Get relative quantity from previous full adjustments.
    $quantity = floatval($order_item->getQuantity());
    $quantity_previous = array_sum(array_column($adjustments_previous, 'quantity'));
    $quantity_relative = $quantity + $quantity_previous;

    // If quantity has changed.
    if ($quantity_relative <> 0) {
      // Get current Order settings for allocation.
      $context = [
        'commerce_order_item' => $order_item,
        'commerce_store' => $order_item->getOrder()->getStore(),
        'inventory_adjustments' => $adjustments_previous,
      ];
      $purchasable_entity = $order_item->getPurchasedEntity();

      // Allocate inventory quantity that hasn't already been allocated to
      // on-hand inventory. Passing in the relative quantity to on-hand
      // adjustments allows for circumstances where on-hand Order Items need to
      // be transitioned back to 'available' for validation before modifying
      // on-hand inventory.
      $allocation = $this->inventoryAllocation->allocate($purchasable_entity, $quantity_relative, $context);

      // Convert values into inventory-quantity field values.
      $quantity_data = array_map(function ($value) {
        return [
          'target_id' => $value['inventory_item_id'],
          'quantity' => floatval($value['quantity']),
        ];
      }, $allocation->toArray());

      // Add item data to order item. (Don't save since it's ran on pre-save).
      $order_item->set('inventory_adjustment_holds', $quantity_data);

      // Use unmodified Order Item to load previous adjustments.
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface|null $unmodified_order_item */
      $unmodified_order_item = (!is_null($order_item->id())) ? $this->orderItemStorage->load($order_item->id()) : NULL;
      $unmodified_adjustment_item_ids = [];
      if (!is_null($unmodified_order_item)) {
        $unmodified_adjustment_item_ids = array_column($unmodified_order_item->get('inventory_adjustment_holds')->getValue(), 'target_id');
      }

      // Invalidate the previous and newly used item cache tags.
      $ids_to_invalidate = array_merge($unmodified_adjustment_item_ids, array_column($quantity_data, 'target_id'));
      foreach (array_unique($ids_to_invalidate) as $inventory_item_id) {
        $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'available');
        $this->cacheFactory->invalidate($cid);
      }
    }
  }

}
