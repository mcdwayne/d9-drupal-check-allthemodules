<?php

namespace Drupal\commerce_recurring_shipping\EventSubscriber;


use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_recurring\Event\RecurringEvents;
use Drupal\commerce_recurring\Event\SubscriptionEvent;
use Drupal\commerce_recurring\RecurringOrderManagerInterface;
use Drupal\commerce_shipping\PackerManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class RecurringOrderShippingSubscriber
 *
 * @package Drupal\commerce_recurring_shipping\EventSubscriber
 */
class RecurringOrderShippingSubscriber implements EventSubscriberInterface {

  /**
   * Recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * Packer manager for shipments.
   *
   * @var \Drupal\commerce_shipping\PackerManagerInterface
   */
  protected $packerManager;

  /**
   * RecurringOrderShippingSubscriber constructor.
   *
   * @param \Drupal\commerce_recurring\RecurringOrderManagerInterface $recurring_order_manager
   * @param \Drupal\commerce_shipping\PackerManagerInterface $packer_manager
   */
  public function __construct(RecurringOrderManagerInterface $recurring_order_manager, PackerManagerInterface $packer_manager) {
    $this->recurringOrderManager = $recurring_order_manager;
    $this->packerManager = $packer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[OrderEvents::ORDER_INSERT] = 'onNewOrder';
    $events[RecurringEvents::SUBSCRIPTION_PRESAVE] = 'onSubscriptionCreate';
    return $events;
  }

  /**
   * Checks whether subscription is shippable.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *
   * @return bool
   */
  public function isSubscriptionShippable(SubscriptionInterface $subscription) {
    if ($subscription->hasField('shipping_profile') && $subscription->hasField('shipping_method')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Reacts on order save.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onNewOrder(OrderEvent $event) {
    $order = $event->getOrder();
    // Check if the order is recurring and has subscriptions.
    $subscriptions = $this->recurringOrderManager->collectSubscriptions($order);
    if (empty($subscriptions)) {
      return;
    }
    $subscription = reset($subscriptions);
    // Check if subscription is shippable.
    if (!$this->isSubscriptionShippable($subscription)) {
      return;
    }
    // Check if the order is shippable and don't have shipments yet.
    if (!$order->hasField('shipments') || !$order->get('shipments')->isEmpty()) {
      return;
    }
    // If we have all the information that is needed.
    if (!$subscription->get('shipping_method')->isEmpty() && !$subscription->get('shipping_profile')->isEmpty()) {
      // We need to apply the shipping costs.
      // First get the shipping profile of the customer.
      $shipping_profile = $subscription->get('shipping_profile')->entity;
      /** @var \Drupal\commerce_shipping\Entity\ShippingMethodInterface $shipping_method */
      $shipping_method = $subscription->get('shipping_method')->entity;
      // Try to apply the shipping method.
      $shipments = [];
      $proposed_shipments = [];
      // Get the possible shipments from packer manager.
      list($proposed_shipments, $removed_shipments) = $this->packerManager->packToShipments($order, $shipping_profile, $proposed_shipments);
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $proposed_shipment */
      foreach ($proposed_shipments as $proposed_shipment) {
        // Find the one that has the shipping method stored in the subscription.
        $shipping_rates = $shipping_method->getPlugin()->calculateRates($proposed_shipment);
        if (!empty($shipping_rates)) {
          $shipping_rate = reset($shipping_rates);
          $shipping_method->getPlugin()
            ->selectRate($proposed_shipment, $shipping_rate);
          $proposed_shipment->setShippingMethod($shipping_method);
          $shipments[] = $proposed_shipment;
        }
      }
      if (!empty($shipments)) {
        $order->set('shipments', $shipments);
        /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
        foreach ($shipments as $shipment) {
          // Shipments without an amount are incomplete / unrated.
          if ($amount = $shipment->getAmount()) {
            $order->addAdjustment(new Adjustment([
              'type' => 'shipping',
              'label' => $shipment->getShippingMethod()->label(),
              'amount' => $amount,
              'source_id' => $shipment->id(),
            ]));
          }
        }
        $order->save();
      }
    }
  }

  /**
   * Reacts on subscription presave.
   *
   * Adds shipping details to subscription.
   *
   * @param \Drupal\commerce_recurring\Event\SubscriptionEvent $event
   */
  public function onSubscriptionCreate(SubscriptionEvent $event) {
    $subscription = $event->getSubscription();
    if (!$this->isSubscriptionShippable($subscription)) {
      return;
    }
    /** @var OrderInterface $order */
    $order = $subscription->getInitialOrder();
    if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {
      foreach ($order->getItems() as $orderItem) {
        if ($orderItem->getPurchasedEntityId() == $subscription->getPurchasedEntityId()) {
          $this->setShippingDetailsFromOrderItem($subscription, $orderItem);
        }
      }
    }
  }

  /**
   * Sets the shipping details from order item.
   *
   * Assumes that subscription is shippable, no extra check is added here, needs
   * to be checked before @see self::isSubscriptionShippable().
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *
   * @return void
   */
  private function setShippingDetailsFromOrderItem(SubscriptionInterface $subscription, OrderItemInterface $order_item) {
    $order = $order_item->getOrder();
    // Check if order was shipped and subscription needs to be shippable too.
    $shipments = $order->get('shipments')->referencedEntities();
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    foreach ($shipments as $shipment) {
      $items = $shipment->getItems();
      // Get the shipment that ships the current order item.
      foreach ($items as $item) {
        if ($item->getOrderItemId() == $order_item->id()) {
          // Set shipping profile and method fields.
          $subscription->set('shipping_profile', $shipment->getShippingProfile());
          $subscription->set('shipping_method', $shipment->getShippingMethod());
          break;
        }
      }
    }
  }

}
