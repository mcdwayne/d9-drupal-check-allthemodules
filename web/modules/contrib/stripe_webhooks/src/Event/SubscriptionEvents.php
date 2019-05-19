<?php

namespace Drupal\stripe_webhooks\Event;

final class SubscriptionEvents {

  /**
   * Name of the event fired after a customer is signed up for a new plan.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-customer.subscription.created
   */
  const CUSTOMER_SUBSCRIPTION_CREATED = 'stripe.webhooks.customer.subscription.created';

  /**
   * Name of the event fired after a customer's subscription ends.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-customer.subscription.deleted
   */
  const CUSTOMER_SUBSCRIPTION_DELETED = 'stripe.webhooks.customer.subscription.deleted';

  /**
   * Name of the event fired three days before the trial period of a
   * subscription is scheduled to end.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-customer.subscription.trial_will_end
   */
  const CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END = 'stripe.webhooks.customer.subscription.trial_will_end';

  /**
   * Name of the event fired after a customer is signed up for a new plan.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api/php#event_types-customer.subscription.updated
   */
  const CUSTOMER_SUBSCRIPTION_UPDATED = 'stripe.webhooks.customer.subscription.updated';

}
