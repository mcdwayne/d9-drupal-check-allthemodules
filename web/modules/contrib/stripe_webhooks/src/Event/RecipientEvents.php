<?php

namespace Drupal\stripe_webhooks\Event;

final class RecipientEvents {

  /**
   * Name of the event fired after a recipient is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-recipient.created
   */
  const RECIPIENT_CREATED = 'stripe.webhooks.recipient.created';

  /**
   * Name of the event fired after a recipient is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-recipient.deleted
   */
  const RECIPIENT_DELETED = 'stripe.webhooks.recipient.deleted';

  /**
   * Name of the event fired after a recipient is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-recipient.updated
   */
  const RECIPIENT_UPDATED = 'stripe.webhooks.recipient.updated';

}
