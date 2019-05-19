<?php

namespace Drupal\stripe_webhooks\Event;

final class BalanceEvents {

  /**
   * Name of the event fired after your Stripe balance has been updated (e.g.,
   * when a charge is available to be paid out). By default, Stripe
   * automatically transfers funds in your balance to your bank account on a
   * daily basis.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-balance.available
   */
  const BALANCE_AVAILABLE = 'stripe.webhooks.balance.available';

}
