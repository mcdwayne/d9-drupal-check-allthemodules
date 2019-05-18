<?php

namespace Drupal\commerce_cib\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event fired when the payment fails.
 *
 * @see \Drupal\commerce_cib\Event\CibEvents
 */
class FailedPayment extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;


  /**
   * Constructs a new FailedPayment object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }

  /**
   * Gets the payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment.
   */
  public function getPayment() {
    return $this->payment;
  }

  /**
   * Sets the payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return $this
   */
  public function setPayment(PaymentInterface $payment) {
    $this->payment = $payment;
    return $this;
  }

}
