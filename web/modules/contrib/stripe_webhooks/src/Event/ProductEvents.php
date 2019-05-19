<?php

namespace Drupal\stripe_webhooks\Event;

final class ProductEvents {

  /**
   * Name of the event fired after a product is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-product.created
   */
  const PRODUCT_CREATED = 'stripe.webhooks.product.created';

  /**
   * Name of the event fired after a product is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-product.deleted
   */
  const PRODUCT_DELETED = 'stripe.webhooks.product.deleted';

  /**
   * Name of the event fired after a product is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-product.updated
   */
  const PRODUCT_UPDATED = 'stripe.webhooks.product.updated';

}
