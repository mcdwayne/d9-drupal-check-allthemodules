<?php

namespace Drupal\commerce_tpay\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Defines the tpay payment event.
 */
class TpayPaymentEvent extends Event {
  
  const TPAY_PAYMENT_RECEIVED = 'commerce_tpay.tpay_payment.received';
  
  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;
  
  /**
   * Constructs a new TpayPaymentEvent.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The order payment.
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }
  
  /**
   * Gets the payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The order payment.
   */
  public function getPayment() {
    return $this->payment;
  }
}
