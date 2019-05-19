<?php

namespace Drupal\stripe_webhooks\Event;

final class OrderEvents {

  /**
   * Name of the event fired after an order is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-order.created
   */
  const ORDER_CREATED = 'stripe.webhooks.order.created';

  /**
   * Name of the event fired after an order payment attempt fails.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-order.payment_failed
   */
  const ORDER_PAYMENT_FAILED = 'stripe.webhooks.order.payment_failed';

  /**
   * Name of the event fired after an order payment attempt succeeds.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-order.payment_succeeded
   */
  const ORDER_PAYMENT_SUCCEEDED = 'stripe.webhooks.order.payment_succeeded';

  /**
   * Name of the event fired after an order is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-order.updated
   */
  const ORDER_UPDATED = 'stripe.webhooks.order.updated';

}
