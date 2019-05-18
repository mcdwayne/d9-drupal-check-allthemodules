<?php

namespace Drupal\commerce_affirm\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the affirm transaction data presend event.
 *
 * @see \Drupal\commerce_affirm\Event\AffirmEvents
 */
class AffirmTransactionDataPreSend extends Event {

  /**
   * The data array to pass to Affirm as checkout object.
   *
   * @var array
   */
  protected $data;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructor.
   *
   * @param array $data
   *   The Affirm js checkout object in an array format.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(array $data, OrderInterface $order) {
    $this->data = $data;
    $this->order = $order;
  }

  /**
   * Gets the data array to pass to Affirm as checkout object.
   *
   * @return array
   *   The data array to pass to Affirm as checkout object.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Sets the data array to pass to Affirm as checkout object.
   *
   * @return array
   *   The data array to pass to Affirm as checkout object.
   */
  public function setData($data) {
    $this->data = $data;
    return $this->data;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

}
