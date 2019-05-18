<?php

namespace Drupal\commerce_cart_refresh\Event;

use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user update a Cart Item's quantity.
 */
class CartItemQuantityChangeEvent extends Event {

  const QUANTITY_CHANGE = 'commerce_cart_refresh.cart_item_quantity_change';

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $item;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;
  
  /**
   * The order's items.
   *
   * @var array
   */
  protected $order_items;

  /**
   * The difference of quantity.
   *
   * @var int
   */
  protected $diff;

  /**
   * Constructs the object.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The current event.
   */
  public function __construct(OrderItemEvent $event) {
    $this->item = $event->getOrderItem();
    $this->order = $this->item->getOrder();

    // Depending on the event used to trigger this one,
    // Cart Item might or might not have the $order attached. 
    if ($this->order instanceof OrderInterface) {
      $this->order_items = $this->order->getItems();
    }
    else {
      $this->order_items = [];
    }

    // Depending on the event used to trigger this one,
    // Cart Item might or might not have an original version attached.
    if ($this->item->original) {
      $this->diff = ($this->item->original->getQuantity() - $this->item->getQuantity());
    }
    else {
      $this->diff = 0;
    }
  }

  /**
   * Get the element that triggered this event.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   */
  public function getItem() {
    return $this->item;
  }
  
  /**
   * Get the element that triggered this event.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Get element that triggered this event.
   *
   * @return string
   */
  public function getDifference() {
    return $this->diff;
  }

  /**
   * Get the new quantity after event finishes.
   *
   * @return string
   */
  public function getNewQuantity() {
    return $this->item->getQuantity();
  }

  /**
   * Get the new quantity before event finishes.
   *
   * @return string
   */
  public function getPreviousQuantity() {
    return $this->item->original->getQuantity();
  }


  /**
   * Get the new Order's quantity after event finishes.
   *
   * @return string
   */
  public function getOrderNewQuantity() {
    $qty = 0;
    foreach ($this->order_items as $item) {
      $qty += $item->getQuantity();
    }
    return $qty;
  }

  /**
   * Get the new Order's quantity before event finishes.
   *
   * @return string
   */
  public function getOrderPreviousQuantity() {
    $qty = ($this->getOrderNewQuantity() + $this->diff);
    if ($this->diff < 0) {
      // Return positive number.
      return -$qty;
    }
    else {
      return $qty;
    }
  }

}
