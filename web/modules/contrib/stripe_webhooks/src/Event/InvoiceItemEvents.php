<?php

namespace Drupal\stripe_webhooks\Event;

final class InvoiceItemEvents {

  /**
   * Name of the event fired after an invoice item is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoiceitem.created
   */
  const INVOICEITEM_CREATED = 'stripe.webhooks.invoiceitem.created';

  /**
   * Name of the event fired after an invoice item is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoiceitem.deleted
   */
  const INVOICEITEM_DELETED = 'stripe.webhooks.invoiceitem.deleted';

  /**
   * Name of the event fired after an invoice item is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoiceitem.updated
   */
  const INVOICEITEM_UPDATED = 'stripe.webhooks.invoiceitem.updated';

}
