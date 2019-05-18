<?php

namespace Drupal\commerce_rental_reservation\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.cancel.post_transition' => ['onCancel'],
      'commerce_order.out.post_transition' => ['onOut'],
      'commerce_order.return.post_transition' => ['onReturn'],
      OrderEvents::ORDER_UPDATE => ['updateOrderItems'],
    ];
  }

  /**
   * Force rental order items to save when an order is updated
   * so that instance selector logic is run and reservations are updated.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   */
  public function updateOrderItems(OrderEvent $event) {
    $order = $event->getOrder();
    // only force save if the order is not a cart.
    if ($order->get('cart')->value != '1'){
      $order_items = $order->getItems();
      foreach ($order_items as $order_item) {
        if ($order_item->hasField('reservation')) {
          $order_item->save();
        }
      }
    }
  }

  /**
   * Sets all rental instances on the order to the OUT status when the order is sent out.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onOut(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $order_items = $order->get('order_items')->referencedEntities();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    foreach ($order_items as $order_item) {
      if ($order_item->hasField('reservation')) {
        $reservations = $order_item->get('reservation')->referencedEntities();
        /** @var \Drupal\commerce_rental_reservation\Entity\RentalReservationInterface $reservation */
        foreach ($reservations as $reservation) {
          /** @var \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface $instance */
          $instance = $reservation->instance->entity;
          $transition = $instance->getState()
            ->getWorkflow()
            ->getTransition('outed');
          $instance->getState()->applyTransition($transition);
          $instance->save();
          drupal_set_message(t('Serial # <strong>%serial</strong> set to <strong>%state</strong>.', array('%serial' => $instance->get('serial')->value, '%state' => $instance->getState()->getLabel())));
        }
      }
    }
  }

  /**
   * Sets all rental instances on the order to the MAINTENANCE status when the order is returned.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onReturn(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $order_items = $order->get('order_items')->referencedEntities();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    foreach ($order_items as $order_item) {
      if ($order_item->hasField('reservation')) {
        $reservations = $order_item->get('reservation')->referencedEntities();
        /** @var \Drupal\commerce_rental_reservation\Entity\RentalReservationInterface $reservation */
        foreach ($reservations as $reservation) {
          /** @var \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface $instance */
          $instance = $reservation->instance->entity;
          $transition = $instance->getState()
            ->getWorkflow()
            ->getTransition('returned');
          $instance->getState()->applyTransition($transition);
          $instance->save();

          $transition = $reservation->getState()
            ->getWorkflow()
            ->getTransition('set_complete');
          $reservation->getState()->applyTransition($transition);
          $reservation->save();
          drupal_set_message(t('Serial # <strong>%serial</strong> set to <strong>%state</strong>.', array('%serial' => $instance->get('serial')->value, '%state' => $instance->getState()->getLabel())));
        }
      }
    }
  }

  /**
   * Cancel all reservations if the order is canceled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onCancel(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $order_items = $order->get('order_items')->referencedEntities();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    foreach ($order_items as $order_item) {
      if ($order_item->hasField('reservation')) {
        $reservations = $order_item->get('reservation')->referencedEntities();
        /** @var \Drupal\commerce_rental_reservation\Entity\RentalReservationInterface $reservation */
        foreach ($reservations as $reservation) {
          $transition = $reservation->getState()
            ->getWorkflow()
            ->getTransition('set_canceled');
          $reservation->getState()->applyTransition($transition);
          $reservation->save();
        }
      }
    }
  }
}