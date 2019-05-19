<?php

namespace Drupal\stripe_webhooks\Event;

final class AccountEvents {

  /**
   * Name of the event fired after an account status or property has changed.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-account.updated
   */
  const ACCOUNT_UPDATED = 'stripe.webhooks.account.updated';

}
