<?php

namespace Drupal\stripe_webhooks\Event;

final class SkuEvents {

  /**
   * Name of the event fired after a SKU is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-sku.created
   */
  const SKU_CREATED = 'stripe.webhooks.sku.created';

  /**
   * Name of the event fired after a SKU is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-sku.deleted
   */
  const SKU_DELETED = 'stripe.webhooks.sku.deleted';

  /**
   * Name of the event fired after a SKU is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-sku.updated
   */
  const SKU_UPDATED = 'stripe.webhooks.sku.updated';

}
