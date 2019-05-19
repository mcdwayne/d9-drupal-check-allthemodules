<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the account event.
 *
 * @see https://stripe.com/docs/api#account_object
 */
class AccountEvent extends Event {

  /**
   * The account.
   *
   * @var \Stripe\Event
   */
  protected $account;

  /**
   * Constructs a new AccountEvent.
   *
   * @param \Stripe\Event $account
   *   The account.
   */
  public function __construct(StripeEvent $account) {
    $this->account = $account;
  }

  /**
   * Gets the account.
   *
   * @return \Stripe\Event
   *   Returns the account.
   */
  public function getAccount() {
    return $this->account;
  }

}
