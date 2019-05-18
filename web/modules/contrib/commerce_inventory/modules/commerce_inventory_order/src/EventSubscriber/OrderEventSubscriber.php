<?php

namespace Drupal\commerce_inventory_order\EventSubscriber;

use Drupal\commerce_inventory_order\InventoryOrderManager;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listen to Commerce Order events.
 */
class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The Order inventory manager.
   *
   * @var \Drupal\commerce_inventory_order\InventoryOrderManager
   */
  protected $manager;

  /**
   * The Commerce Log entity storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructs a new OrderEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_inventory_order\InventoryOrderManager $inventory_order_manager
   *   The Order Inventory manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, InventoryOrderManager $inventory_order_manager) {
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
    $this->manager = $inventory_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Make all workflow transitions point to the catch-all subscriber method.
    // @todo load all possible transactions dynamically.
    // Dynamic transition loading currently errors on Drush cache clear stating
    // that the container isn't set, which is what is needed to load the
    // "plugin.manager.workflow" service. Until then, custom modules with
    // different Order states will only need to write a custom subscriber that
    // mimics ::transitionOrder to apply the relevant adjustment state.
    $events['commerce_order.cancel.pre_transition'] = 'preTransitionOrder';
    $events['commerce_order.fulfill.pre_transition'] = 'preTransitionOrder';
    $events['commerce_order.place.pre_transition'] = 'preTransitionOrder';
    $events['commerce_order.validate.pre_transition'] = 'preTransitionOrder';

    $events['commerce_order.cancel.post_transition'] = 'postTransitionOrder';
    $events['commerce_order.fulfill.post_transition'] = 'postTransitionOrder';
    $events['commerce_order.place.post_transition'] = 'postTransitionOrder';
    $events['commerce_order.validate.post_transition'] = 'postTransitionOrder';

    // Track Order Item Updates.
    // $events[OrderEvents::ORDER_ITEM_PREDELETE] = 'preDeleteOrderItem';.
    $events[OrderEvents::ORDER_ITEM_PRESAVE] = 'preSaveOrderItem';
    $events[OrderEvents::ORDER_ITEM_UPDATE] = 'postSaveOrderItem';

    return $events;
  }

  /**
   * Handle inventory adjustments on Order pre-transitions.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   * @param string $event_name
   *   The name of the event.
   */
  public function preTransitionOrder(WorkflowTransitionEvent $event, $event_name) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // Get current transition from event name.
    $order_transition_id = explode('.', $event_name)[1];

    // Get all available order item transitions.
    $transitions = $this->manager->getAllBundleInventoryWorkflowTransitions();

    // Exit early if this transition isn't tracked under any Order Item bundle.
    if (!array_key_exists($order_transition_id, $transitions)) {
      return;
    }

    // Track Order Item fallback state for.
    $states = [];
    foreach ($transitions[$order_transition_id] as $item_bundle_id => $item_data) {
      $states[$item_bundle_id] = $item_data['state'];
    }
    $order->setData('item_inventory_adjustment_states', $states);
  }

  /**
   * Handle inventory adjustments on Order post-transitions.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   * @param string $event_name
   *   The name of the event.
   */
  public function postTransitionOrder(WorkflowTransitionEvent $event, $event_name) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // Get current transition from event name.
    $order_transition_id = explode('.', $event_name)[1];

    // Get all available order item transitions.
    $transitions = $this->manager->getAllBundleInventoryWorkflowTransitions();

    // Exit early if this transition isn't tracked under any Order Item bundle.
    if (!array_key_exists($order_transition_id, $transitions)) {
      return;
    }

    // Run through each Order Item to adjust the inventory workflow state.
    foreach ($order->getItems() as $order_item) {
      $order_item_transition = NestedArray::getValue($transitions, [
        $order_transition_id,
        $order_item->bundle(),
        'transition',
      ]);

      // Exit early if the order transition shouldn't update.
      if (is_null($order_item_transition)) {
        continue;
      }

      // Transition the Order Item inventory workflow state.
      if ($this->manager::transitionAdjustmentState($order_item, $order_item_transition)) {
        $order_item->save();
      }
    }
  }

  /**
   * Handle Order Item pre-deletions.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The Order Item event.
   */
  public function preDeleteOrderItem(OrderItemEvent $event) {
    $order_item = $event->getOrderItem();

    // Exit early if Order isn't set.
    if (is_null($order_item->getOrder())) {
      return;
    }

    $this->manager->deleteInventory($order_item);
  }

  /**
   * Handle Order Item pre-saves.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The Order Item event.
   */
  public function preSaveOrderItem(OrderItemEvent $event) {
    $order_item = $event->getOrderItem();

    // Exit early if Order isn't set.
    if (is_null($order_item->getOrderId()) || is_null($order_item->id())) {
      return;
    }

    // Make un-tracked workflow adjustments.
    if ($this->manager::checkAdjustmentState($order_item, 'untracked')) {
      $this->manager->cancelInventoryHolds($order_item);
    }

    // Make available workflow adjustments.
    if ($this->manager::checkAdjustmentState($order_item, 'available')) {
      $this->manager->makeInventoryHolds($order_item);
    }

    // Make on-hand workflow adjustments.
    if ($this->manager::checkAdjustmentState($order_item, 'on_hand')) {
      $this->manager->makeInventoryHolds($order_item);
      $this->manager->convertInventoryHolds($order_item);
    }
  }

  /**
   * Handle Order Item post-saves.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The Order Item event.
   */
  public function postSaveOrderItem(OrderItemEvent $event) {
    $order_item = $event->getOrderItem();

    // Exit early if Order isn't set.
    if (is_null($order_item->getOrderId()) || is_null($order_item->id())) {
      return;
    }

    if (!$order_item->get('inventory_adjustment_holds')->isEmpty()) {
      foreach ($order_item->get('inventory_adjustment_holds') as $hold) {
        // Add to logs.
        $this->logStorage->generate($hold->entity, 'inventory_item_adjustment_available', [
          'order_id' => $order_item->getOrderId(),
          'quantity' => $hold->quantity,
        ])->save();
        $this->logStorage->generate($order_item->getOrder(), 'order_item_inventory_adjustment_available', [
          'purchasable_entity_label' => $order_item->getPurchasedEntity()->label(),
          'quantity' => $hold->quantity,
          'inventory_location_label' => $hold->entity->getLocation()->label(),
        ])->save();
      }
    }

  }

}
