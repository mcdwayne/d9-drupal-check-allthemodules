<?php

namespace Drupal\stripe_webhooks\Event;

final class SourceTransactionEvents {

  /**
   * Name of the event fired after a source transaction is canceled.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.transaction.created
   */
  const SOURCE_TRANSACTION_CREATED = 'stripe.webhooks.source.transaction.created';

}
