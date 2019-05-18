<?php

namespace Drupal\braintree_cashier\Event;

use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched when there's an error interacting with the Braintree API.
 */
class BraintreeErrorEvent extends Event {

  /**
   * The user entity.
   *
   * @var \Drupal\user\Entity\User
   *   The user entity.
   */
  protected $user;

  /**
   * The error message provided by Braintree.
   *
   * @var string
   */
  protected $errorMessage;

  /**
   * The result object from the Braintree API.
   *
   * @var mixed
   */
  protected $result;

  /**
   * PaymentMethodOrCustomerCreateErrorEvent constructor.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $error_message
   *   The error message provided by Braintree.
   * @param mixed $result
   *   The result object from the Braintree API.
   */
  public function __construct(User $user, $error_message, $result) {
    $this->user = $user;
    $this->errorMessage = $error_message;
    $this->result = $result;
  }

  /**
   * Gets the user that attempted to create a payment method or customer.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Gets the error message provided by Braintree.
   *
   * @return string
   *   The error message.
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * Gets the Braintree result object.
   *
   * @return mixed
   *   The Braintree result.
   */
  public function getResult() {
    return $this->result;
  }

}
