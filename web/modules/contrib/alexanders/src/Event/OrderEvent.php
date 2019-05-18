<?php

namespace Drupal\alexanders\Event;

use Drupal\alexanders\Entity\AlexandersOrderInterface;
use Drupal\alexanders\Entity\AlexandersOrderItem;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order event.
 *
 * @see \Drupal\alexanders\Event\OrderEvents
 */
class OrderEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new OrderEvent.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrderInterface $order
   *   The order.
   */
  public function __construct(AlexandersOrderInterface $order) {
    $this->order = $order;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\alexanders\Entity\AlexandersOrderInterface
   *   Gets the order.
   */
  public function getOrder() {
    return $this->order;
  }

}
