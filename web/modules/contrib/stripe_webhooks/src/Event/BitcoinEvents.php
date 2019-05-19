<?php

namespace Drupal\stripe_webhooks\Event;

final class BitcoinEvents {

  /**
   * Name of the event fired after a receiver has been created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-bitcoin.receiver.created
   */
  const BITCOIN_RECEIVER_CREATED = 'stripe.webhooks.bitcoin.receiver.created';

  /**
   * Name of the event fired after a receiver is filled (i.e., when it has
   * received enough bitcoin to process a payment of the same amount).
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-bitcoin.receiver.filled
   */
  const BITCOIN_RECEIVER_FILLED = 'stripe.webhooks.bitcoin.receiver.filled';

  /**
   * Name of the event fired after a receiver has been updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-bitcoin.receiver.updated
   */
  const BITCOIN_RECEIVER_UPDATED = 'stripe.webhooks.bitcoin.receiver.updated';

  /**
   * Name of the event fired after bitcoin is pushed to a receiver.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-bitcoin.receiver.transaction.created
   */
  const BITCOIN_RECEIVER_TRANSACTION_CREATED = 'stripe.webhooks.bitcoin.receiver.transaction.created';

}
