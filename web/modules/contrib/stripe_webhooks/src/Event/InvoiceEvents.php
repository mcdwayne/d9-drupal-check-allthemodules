<?php

namespace Drupal\stripe_webhooks\Event;

final class InvoiceEvents {

  /**
   * Name of the event fired after a new invoice is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoice.created
   */
  const INVOICE_CREATED = 'stripe.webhooks.invoice.created';

  /**
   * Name of the event fired after an invoice payment attempt fails, either due
   * to a declined payment or the lack of a stored payment method.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoice.payment_failed
   */
  const INVOICE_PAYMENT_FAILED = 'stripe.webhooks.invoice.payment_failed';

  /**
   * Name of the event fired after an invoice payment attempt succeeds.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoice.payment_succeeded
   */
  const INVOICE_PAYMENT_SUCCEEDED = 'stripe.webhooks.invoice.payment_succeeded';

  /**
   * Name of the event fired X number of days before a subscription is scheduled
   * to create an invoice that is charged automatically, where X is determined
   * by your subscriptions settings.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoice.upcoming
   */
  const INVOICE_UPCOMING = 'stripe.webhooks.invoice.upcoming';

  /**
   * Name of the event fired after an invoice changes (e.g., invoice amount).
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-invoice.updated
   */
  const INVOICE_UPDATED = 'stripe.webhooks.invoice.updated';

}
