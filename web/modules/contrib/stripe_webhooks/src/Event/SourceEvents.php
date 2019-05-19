<?php

namespace Drupal\stripe_webhooks\Event;

final class SourceEvents {

  /**
   * Name of the event fired after a source is canceled.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.canceled
   */
  const SOURCE_CANCELED = 'stripe.webhooks.source.canceled';

  /**
   * Name of the event fired after a source transitions to chargeable.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.chargeable
   */
  const SOURCE_CHARGEABLE = 'stripe.webhooks.source.chargeable';

  /**
   * Name of the event fired after a source fails.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.failed
   */
  const SOURCE_FAILED = 'stripe.webhooks.source.failed';

}
