<?php

namespace Drupal\commerce_cib\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event fired after the transaction initialization fails.
 *
 * @see \Drupal\commerce_cib\Event\CibEvents
 */
class NoCommunication extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new NoCommunication object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(OrderInterface $order) {
    $this->order = $order;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Sets the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return $this
   */
  public function setOrder(OrderInterface $order) {
    $this->order = $order;
    return $this;
  }

}
