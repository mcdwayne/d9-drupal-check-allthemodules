<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Order;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\ShippingInformationInterface;

/**
 * An interface to describe captures.
 */
interface CreateCaptureInterface extends ObjectInterface, RequestInterface {

  /**
   * Sets the captured amount.
   *
   * @param int $amount
   *   The captured amount in minor units.
   *
   * @return $this
   *   The self.
   */
  public function setCapturedAmount(int $amount) : CreateCaptureInterface;

  /**
   * Sets the description.
   *
   * @param string $description
   *   The description.
   *
   * @return $this
   *   The self.
   */
  public function setDescription(string $description) : CreateCaptureInterface;

  /**
   * Sets the order items.
   *
   * @param array $orderItems
   *   The order items.
   *
   * @return $this
   *   The self.
   */
  public function setOrderItems(array $orderItems) : CreateCaptureInterface;

  /**
   * Adds an order item.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface $orderItem
   *   The order item.
   *
   * @return $this
   *   The self.
   */
  public function addOrderItem(OrderItemInterface $orderItem) : CreateCaptureInterface;

  /**
   * Sets the shipping information.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\ShippingInformationInterface $info
   *   The shipping information.
   *
   * @return $this
   *   The self.
   */
  public function setShippingInformation(ShippingInformationInterface $info) : CreateCaptureInterface;

  /**
   * Sets the shipping delay.
   *
   * @param int $delay
   *   Delay before the order will be shipped.
   *
   * @return $this
   *   The self.
   */
  public function setShippingDelay(int $delay) : CreateCaptureInterface;

}
