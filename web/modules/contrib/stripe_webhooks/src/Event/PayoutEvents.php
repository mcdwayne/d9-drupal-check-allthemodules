<?php

namespace Drupal\stripe_webhooks\Event;

final class PayoutEvents {

  /**
   * Name of the event fired after an payout is canceled.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-payout.canceled
   */
  const PAYOUT_CANCELED = 'stripe.webhooks.payout.canceled';

  /**
   * Name of the event fired after an payout is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-payout.created
   */
  const PAYOUT_CREATED = 'stripe.webhooks.payout.created';

  /**
   * Name of the event fired after a payout attempt fails.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-payout.failed
   */
  const PAYOUT_FAILED = 'stripe.webhooks.payout.failed';

  /**
   * Name of the event fired after a payout is expected to be available in the
   * destination account. If the payout fails, a payout.failed notification is
   * additionally sent at a later time.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-payout.paid
   */
  const PAYOUT_PAID = 'stripe.webhooks.payout.paid';

  /**
   * Name of the event fired after the metadata of a payout is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-payout.updated
   */
  const PAYOUT_UPDATED = 'stripe.webhooks.payout.updated';

}
