<?php

namespace Drupal\stripe_webhooks\Event;

final class TransferEvents {

  /**
   * Name of the event fired after a transfer is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-transfer.created
   */
  const TRANSFER_CREATED = 'stripe.webhooks.transfer.created';

  /**
   * Name of the event fired after a transfer is reversed, including partial
   * reversals.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-transfer.reversed
   */
  const TRANSFER_REVERSED = 'stripe.webhooks.transfer.reversed';

  /**
   * Name of the event fired after the description or metadata of a transfer is
   * updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-transfer.updated
   */
  const TRANSFER_UPDATED = 'stripe.webhooks.transfer.updated';

}
