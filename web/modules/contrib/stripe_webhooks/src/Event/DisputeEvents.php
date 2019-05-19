<?php

namespace Drupal\stripe_webhooks\Event;

final class DisputeEvents {

  /**
   * Name of the event fired after a dispute is closed and the dispute status
   * changes to charge_refunded, lost, warning_closed, or won.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.dispute.closed
   */
  const CHARGE_DISPUTE_CLOSED = 'stripe.webhooks.charge.dispute.closed';

  /**
   * Name of the event fired after a customer disputes a charge with their bank.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.dispute.created
   */
  const CHARGE_DISPUTE_CREATED = 'stripe.webhooks.charge.dispute.created';

  /**
   * Name of the event fired after funds are reinstated to your account after a
   * dispute is won.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.dispute.funds_reinstated
   */
  const CHARGE_DISPUTE_REFUNDS_REINSTATED = 'stripe.webhooks.charge.dispute.funds_reinstated';

  /**
   * Name of the event fired after funds are removed from your account due to a
   * dispute.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.dispute.funds_withdrawn
   */
  const CHARGE_DISPUTE_FUNDS_WITHDRAWN = 'stripe.webhooks.charge.dispute.funds_withdrawn';

  /**
   * Name of the event fired after the dispute is updated (usually with
   * evidence).
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-charge.dispute.updated
   */
  const CHARGE_DISPUTE_UPDATED = 'stripe.webhooks.charge.dispute.updated';

}
