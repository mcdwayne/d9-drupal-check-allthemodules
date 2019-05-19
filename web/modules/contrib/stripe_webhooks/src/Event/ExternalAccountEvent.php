<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Account;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the external account event.
 *
 * @see https://stripe.com/docs/api#account_card_object
 * @see https://stripe.com/docs/api#account_bank_account_object
 */
class ExternalAccountEvent extends Event {

  /**
   * The external account.
   *
   * @var \Stripe\Event
   */
  protected $externalAccount;

  /**
   * Constructs a new ExternalAccountEvent.
   *
   * @param \Stripe\Event $external_account
   *   The external account.
   */
  public function __construct(StripeEvent $external_account) {
    $this->externalAccount = $external_account;
  }

  /**
   * Gets the account of the external account.
   *
   * @return \Stripe\Account
   *   Returns the account of the external account.
   */
  public function getAccount() {
    return Account::retrieve($this->externalAccount->__get('data')['object']['account']);
  }

  /**
   * Gets the external account.
   *
   * @return \Stripe\Event
   *   Returns the external account.
   */
  public function getExternalAccount() {
    return $this->externalAccount;
  }

}
