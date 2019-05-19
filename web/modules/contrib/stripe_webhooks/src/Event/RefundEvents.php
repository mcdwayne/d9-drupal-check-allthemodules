<?php

namespace Drupal\stripe_webhooks\Event;

final class RefundEvents {

  /**
   * Name of the event fired after a refund is updated on selected payment
   * methods.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.refund.updated
   */
  const CHARGE_REFUND_UPDATED = 'stripe.webhooks.charge.refund.updated';

}
