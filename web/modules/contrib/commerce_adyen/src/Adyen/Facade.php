<?php

namespace Drupal\commerce_adyen\Adyen;

/**
 * Abstraction for storing an order and payment method in the object.
 */
trait Facade {

  /**
   * Entity order.
   *
   * @var \stdClass
   */
  private $order;

  /**
   * Payment method definition.
   *
   * @var array
   */
  private $paymentMethod = [];

  /**
   * Set Order.
   *
   * @param \stdClass $order
   *   Order object.
   */
  public function setOrder(\stdClass $order) {
    $this->order = $order;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Set payment method.
   *
   * @param array $payment_method
   *   Payment method definition.
   */
  public function setPaymentMethod(array $payment_method) {
    $this->paymentMethod = $payment_method;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->paymentMethod;
  }

}
