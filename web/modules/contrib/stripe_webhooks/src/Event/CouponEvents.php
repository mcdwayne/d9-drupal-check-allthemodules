<?php

namespace Drupal\stripe_webhooks\Event;

final class CouponEvents {

  /**
   * Name of the event fired after a coupon is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-coupon.created
   */
  const COUPON_CREATED = 'stripe.webhooks.coupon.created';

  /**
   * Name of the event fired after a coupon is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-coupon.deleted
   */
  const COUPON_DELETED = 'stripe.webhooks.coupon.deleted';

  /**
   * Name of the event fired after a coupon is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-coupon.updated
   */
  const COUPON_UPDATED = 'stripe.webhooks.coupon.updated';

}
