<?php

namespace Drupal\uc_fulfillment\Event;

use Drupal\uc_fulfillment\ShipmentInterface;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a shipment is saved.
 */
class ShipmentSaveEvent extends Event {

  const EVENT_NAME = 'uc_fulfillment_shipment_save';

  /**
   * The order.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  public $order;

  /**
   * The shipment.
   *
   * @var \Drupal\uc_fulfillment\ShipmentInterface
   */
  public $shipment;

  /**
   * Constructs the object.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param \Drupal\uc_fulfillment\ShipmentInterface $shipment
   *   The shipment.
   */
  public function __construct(OrderInterface $order, ShipmentInterface $shipment) {
    $this->order = $order;
    $this->shipment = $shipment;
  }

}
