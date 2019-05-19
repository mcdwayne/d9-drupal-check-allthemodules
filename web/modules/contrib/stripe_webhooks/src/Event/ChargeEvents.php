<?php

namespace Drupal\stripe_webhooks\Event;

final class ChargeEvents {

  /**
   * Name of the event fired after a failed charge attempt occurs.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.failed
   */
  const CHARGE_FAILED = 'stripe.webhooks.charge.failed';

  /**
   * Name of the event fired after a pending charge is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.pending
   */
  const CHARGE_PENDING = 'stripe.webhooks.charge.pending';

  /**
   * Name of the event fired after a charge is refunded, including partial
   * refunds.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.refunded
   */
  const CHARGE_REFUNDED = 'stripe.webhooks.charge.refunded';

  /**
   * Name of the event fired after new charge is created and is successful.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.succeeded
   */
  const CHARGE_SUCCEEDED = 'stripe.webhooks.charge.succeeded';

  /**
   * Name of the event fired after a charge description or metadata is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.updated
   */
  const CHARGE_UPDATED = 'stripe.webhooks.charge.updated';

}
