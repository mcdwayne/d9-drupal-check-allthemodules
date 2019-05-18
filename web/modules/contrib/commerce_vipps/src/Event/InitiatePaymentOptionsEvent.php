<?php

namespace Drupal\commerce_vipps\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

class InitiatePaymentOptionsEvent extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * Array of options.
   *
   * @var array
   *
   * @see https://github.com/zaporylie/php-vipps/blob/2.x/src/Api/Payment.php#L195-L222
   */
  protected $options = [];

  /**
   * Constructs a new InitiatePaymentOptionsEvent.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param array $options
   *   Options.
   */
  public function __construct(PaymentInterface $payment, array $options = []) {
    $this->payment = $payment;
    $this->options = $options;
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
   * Vipps options.
   *
   * @return array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Vipps options.
   *
   * @param array $options
   */
  public function setOptions(array $options) {
    $this->options = $options;
    return $this;
  }

}
