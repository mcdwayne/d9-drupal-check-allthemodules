<?php

namespace Drupal\commerce_rental_reservation\EventSubscriber;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_rental_reservation\Entity\RentalInstanceType;
use Drupal\commerce_rental_reservation\Entity\RentalReservation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class OrderItemEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      OrderEvents::ORDER_ITEM_PRESAVE => ['rentalItemAdd'],
      OrderEvents::ORDER_ITEM_DELETE => ['rentalItemDelete']
    ];
    return $events;
  }

  /**
   * Runs the rental instance selector and creates reservations for rental order items.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   */
  public function rentalItemAdd(OrderItemEvent $event) {
    // @TODO: IF RENTAL PERIOD CHANGES, NEED TO RE-CHECK AVAILABILITY
    $order_item = $event->getOrderItem();
    $order = $order_item->getOrder();
    if (!empty($order) && $order_item->hasField('reservation'))  {
      $order_type = OrderType::load($order->bundle());
      $order_period_enabled = $order_type->getThirdPartySetting('commerce_rental_reservation', 'enable_order_period');
      // instances are not assigned when in the cart, they will be added when the checkout is complete.
      if ($order->get('cart')->value != '1' && in_array($order->getState()->value, ['draft', 'fulfillment', 'outed'])) {
        /** @var \Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector\RentalInstanceSelectorPluginInterface $selector */
        $selector = $this->getInstanceSelector($order_item);
        // Use the instance selector if instance is empty
        if (empty($order_item->instance->entity) && $order_item->id() && ($instance = $selector->selectOrderItemInstance($order_item)) !== NULL) {
          $order_item->instance[] = $instance;
        }
      }
      if (!empty($order_item->instance->entity)) {
        if (empty($order_item->reservation->entity)) {
          // @TODO: Support multiple reservation types?
          $reservation = RentalReservation::create([
            'type' => 'default',
            'order_id' => $order->id(),
            'order_item_id' => $order_item->id(),
            'variation' => $order_item->getPurchasedEntityId(),
            'period' => $order_period_enabled ? $order->get('order_period') : NULL,
            'instance' => $order_item->instance->entity->id(),
            'state' => 'active',
          ]);
          $reservation->save();
          $order_item->reservation = $reservation;
        } else if (!$order_item->isNew()) {
          $this->updateReservation($order_item, $order_period_enabled);
        }
      } else {
        drupal_set_message(t('No instances available for %label', ['%label' => $order_item->getPurchasedEntity()->label()]), 'warning');
      }
    }
  }

  public function rentalItemDelete(OrderItemEvent $event) {
    $order_item = $event->getOrderItem();
    /** @var \Drupal\commerce_rental_reservation\Entity\RentalReservation $reservation */
    if ($order_item->hasField('reservation') && $reservation = $order_item->reservation->entity)  {
      $reservation->delete();
    }
  }


  /**
   * Update reservation fields
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   * @param bool $order_period_enabled
   */
  protected function updateReservation(OrderItemInterface $order_item, bool $order_period_enabled) {
    /** @var \Drupal\commerce_rental_reservation\Entity\RentalReservationInterface $reservation */
    if ($reservation = $order_item->reservation->entity) {
      $reservation->title =  $order_item->instance->entity->serial;
      $reservation->period =  $order_period_enabled ? $order_item->getOrder()->get('order_period') : NULL;
      $reservation->instance->entity = $order_item->instance->entity;
      $reservation->variation->entity = $order_item->getPurchasedEntity();
      $reservation->save();
    }
  }

  /**
   * Gets the instance selector plugin for the instance assigned to an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *
   * @return \Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector\RentalInstanceSelectorPluginInterface
   */
  protected function getInstanceSelector(OrderItemInterface $order_item) {
    $variation = $order_item->getPurchasedEntity();
    $variation_type = ProductVariationType::load($variation->bundle());
    $instance_type = RentalInstanceType::load($variation_type->getThirdPartySetting('commerce_rental_reservation', 'rental_instance_type'));
    return $instance_type->getSelector();
  }

}
