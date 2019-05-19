<?php

namespace Drupal\stripe_webhooks\Event;

final class PlanEvents {

  /**
   * Name of the event fired after a plan is created.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-plan.created
   */
  const PLAN_CREATED = 'stripe.webhooks.plan.created';

  /**
   * Name of the event fired after a plan is deleted.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-plan.deleted
   */
  const PLAN_DELETED = 'stripe.webhooks.plan.deleted';

  /**
   * Name of the event fired after a plan is updated.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-plan.updated
   */
  const PLAN_UPDATED = 'stripe.webhooks.plan.updated';

}
