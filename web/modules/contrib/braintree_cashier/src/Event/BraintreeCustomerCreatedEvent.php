<?php

namespace Drupal\braintree_cashier\Event;

use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * A Braintree customer has been created for a user.
 */
class BraintreeCustomerCreatedEvent extends Event {

  /**
   * The user account for which the Braintree customer was created.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * BraintreeCustomerCreatedEvent constructor.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user for whom the Braintree customer was created.
   */
  public function __construct(User $user) {
    $this->user = $user;
  }

  /**
   * Gets the user account entity.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function getUser() {
    return $this->user;
  }

}
