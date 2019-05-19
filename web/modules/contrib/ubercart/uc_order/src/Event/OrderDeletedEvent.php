<?php

namespace Drupal\uc_order\Event;

use Drupal\uc_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when an order is being deleted.
 */
class OrderDeletedEvent extends Event {

  const EVENT_NAME = 'uc_order_delete';

  /**
   * The order.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  public $order;

  /**
   * Constructs the object.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   */
  public function __construct(OrderInterface $order) {
    $this->order = $order;
  }

}
