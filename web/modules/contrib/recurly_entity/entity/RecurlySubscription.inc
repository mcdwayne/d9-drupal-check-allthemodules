<?php

class RecurlySubscription extends RecurlyEntity {

  /**
   * Override __construct to force an entity type.
   */
  public function __construct(array $values = [], $entityType = 'recurly_subscription') {
    // TODO: Can this be moved up to RecurlyEntity for everything with a UUID?
    // We need to map to a local ID if possible. This can happen if a plan has
    // been saved locally but our entity cache is empty.
    if (!isset($values['id'])) {
      $id = entity_get_controller('recurly_subscription')
        ->entityId($values['uuid']);
      if ($id) {
        $values['id'] = $id;
      }
    }

    // Extract the plan code from this subscription.
    if (!isset($values['plan_code'])) {
      $values['plan_code'] = $values['plan']->plan_code;
    }

    // Generate a title for this subscription.
    if (!isset($values['title'])) {
      $subscription_starts = $values['current_period_started_at']->format('Y-m-d');
      $subscription_ends = $values['current_period_ends_at']->format('Y-m-d');

      // We explicitly don't want to translate these user-supplied fields.
      $values['title'] = format_string('!name (!start to !end)', ['!name' => $values['plan']->name, '!start' => $subscription_starts, '!end' => $subscription_ends]);
    }

    $this->setOwner($values);

    parent::__construct($values, $entityType);
  }

  /**
   * Override uri() for use with entity_uri().
   */
  public function uri() {
    return [
      'path' => 'recurly/subscription/' . $this->id,
    ];
  }

  /**
   * Return the subscription plan associated with this subscription.
   *
   * @return RecurlySubscriptionPlan
   *   The Recurly Subscription Plan entity associated with this subscription.
   */
  public function plan() {
    return entity_get_controller('recurly_subscription_plan')
      ->loadByPlanCode($this->plan_code);
  }
}
