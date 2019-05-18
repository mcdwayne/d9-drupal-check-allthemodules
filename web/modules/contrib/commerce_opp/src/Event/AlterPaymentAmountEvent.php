<?php

namespace Drupal\commerce_opp\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for altering the charged payment amount of OPP payments.
 *
 * @see \Drupal\commerce_opp\Event\OpenPaymentPlatformPaymentEvents
 */
class AlterPaymentAmountEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The payment amount.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $paymentAmount;

  /**
   * Constructs a new AlterPaymentAmountEvent object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(OrderInterface $order) {
    $this->order = $order;
    $this->paymentAmount = $order->getTotalPrice();
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

  /**
   * Gets the payment amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The payment amount.
   */
  public function getPaymentAmount() {
    return $this->paymentAmount;
  }

  /**
   * Sets the payment amount.
   *
   * @param \Drupal\commerce_price\Price $payment_mount
   *   The payment amount.
   */
  public function setPaymentAmount(Price $payment_mount) {
    $this->paymentAmount = $payment_mount;
  }

}
