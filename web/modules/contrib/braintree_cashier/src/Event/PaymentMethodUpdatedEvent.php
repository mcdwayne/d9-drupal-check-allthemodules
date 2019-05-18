<?php

namespace Drupal\braintree_cashier\Event;

use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * A user has updated their payment method.
 */
class PaymentMethodUpdatedEvent extends Event {

  /**
   * The user that updated their payment method.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;


  /**
   * The payment method type. One of the Braintree payment method class names.
   *
   * @var string
   *
   * @see https://developers.braintreepayments.com/reference/response/payment-method/php
   */
  protected $paymentMethodType;

  /**
   * PaymentMethodUpdatedEvent constructor.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user that updated their payment method.
   * @param string $payment_method_type
   *   Payment method type.
   */
  public function __construct(User $user, $payment_method_type) {
    $this->user = $user;
    $this->paymentMethodType = $payment_method_type;
  }

  /**
   * Gets the user that updated their payment method.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Gets the payment method type.
   *
   * @return string
   *   The payment method type.
   */
  public function getPaymentMethodType() {
    return $this->paymentMethodType;
  }

}
