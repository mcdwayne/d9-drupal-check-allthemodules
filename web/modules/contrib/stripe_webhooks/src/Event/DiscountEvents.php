<?php

namespace Drupal\stripe_webhooks\Event;

final class DiscountEvents {

  /**
   * Name of the event fired after a coupon is attached to a customer.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-customer.discount.created
   */
  const CUSTOMER_DISCOUNT_CREATED = 'stripe.webhooks.customer.discount.created';

  /**
   * Name of the event fired after a coupon is removed from a customer.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-customer.discount.deleted
   */
  const CUSTOMER_DISCOUNT_DELETED = 'stripe.webhooks.customer.discount.deleted';

  /**
   * Name of the event fired after a customer is switched from one coupon to
   * another.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-customer.discount.updated
   */
  const CUSTOMER_DISCOUNT_UPDATED = 'stripe.webhooks.customer.discount.updated';

}
