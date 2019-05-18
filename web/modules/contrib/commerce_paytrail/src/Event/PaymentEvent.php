<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Payment event.
 */
class PaymentEvent extends Event {

  protected $payment;

  /**
   * Constructs a new instance.
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
  public function getPayment() : PaymentInterface {
    return $this->payment;
  }

}
