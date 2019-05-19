<?php

namespace Drupal\stripe_webhooks\Event;

final class CustomerSourceEvents {

  /**
   * Name of the event fired after a new source is created for a customer.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.created
   */
  const CUSTOMER_SOURCE_CREATED = 'stripe.webhooks.customer.source.created';

  /**
   * Name of the event fired after a source is removed from a customer.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.deleted
   */
  const CUSTOMER_SOURCE_DELETED = 'stripe.webhooks.customer.source.deleted';

  /**
   * Name of the event fired after a source's details are changed.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-source.updated
   */
  const CUSTOMER_SOURCE_UPDATED = 'stripe.webhooks.customer.source.updated';

}
