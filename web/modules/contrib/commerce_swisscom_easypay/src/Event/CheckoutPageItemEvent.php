<?php

namespace Drupal\commerce_swisscom_easypay\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event to modify the checkout page item sent to the checkout page.
 *
 * @package Drupal\commerce_swisscom_easypay\Event
 */
class CheckoutPageItemEvent extends Event {

  /**
   * The commerce order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  private $order;

  /**
   * Holds all data of the checkout page item.
   *
   * @var array
   *
   * @see https://www.swisscom.ch/content/dam/swisscom/de/biz/etp/angebote/easypay-cop-interface-manual.pdf.
   */
  private $data = [];

  /**
   * Constructs the CheckoutPageItemEvent event.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   */
  public function __construct(OrderInterface $order) {
    $this->order = $order;
  }

  /**
   * Set all data of the checkout page item.
   *
   * @param array $data
   *   Associative array with all data.
   */
  public function setData(array $data) {
    $this->data = $data;
  }

  /**
   * Get all data of the checkout page item.
   *
   * @return array
   *   Associative array with all data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Set a single field for the checkout page item.
   *
   * @param string $fieldName
   *   Name of the field.
   * @param mixed $fieldValue
   *   Value of the field.
   */
  public function setField($fieldName, $fieldValue) {
    $this->data[$fieldName] = $fieldValue;
  }

}
