<?php

namespace Drupal\stripe_webhooks\Event;

final class CustomerEvents {

  /**
   * Name of the event fired after a new customer is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-customer.created
   */
  const CUSTOMER_CREATED = 'stripe.webhooks.customer.created';

  /**
   * Name of the event fired after a customer is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-customer.deleted
   */
  const CUSTOMER_DELETED = 'stripe.webhooks.customer.deleted';

  /**
   * Name of the event fired after any property of a customer changes.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-customer.updated
   */
  const CUSTOMER_UPDATED = 'stripe.webhooks.customer.updated';

}
