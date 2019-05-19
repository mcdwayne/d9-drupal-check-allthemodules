<?php

namespace Drupal\stripe_webhooks\Event;

final class OrderReturnEvents {

  /**
   * Name of the event fired after an order return is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-order_return.created
   */
  const ORDER_RETURN_CREATED = 'stripe.webhooks.order_return.created';

}
