<?php

namespace Drupal\stripe_webhooks\Event;

final class ExternalAccountEvents {

  /**
   * Name of the event fired after an external account is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-account.external_account.created
   */
  const ACCOUNT_EXTERNAL_ACCOUNT_CREATED = 'stripe.webhooks.account.external_account.created';

  /**
   * Name of the event fired after an external account is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-account.external_account.deleted
   */
  const ACCOUNT_EXTERNAL_ACCOUNT_DELETED = 'stripe.webhooks.account.external_account.deleted';

  /**
   * Name of the event fired after an external account is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-account.external_account.updated
   */
  const ACCOUNT_EXTERNAL_ACCOUNT_UPDATED = 'stripe.webhooks.account.external_account.updated';

}
