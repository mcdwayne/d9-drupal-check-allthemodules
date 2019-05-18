<?php

/**
 * @class
 * The entity representing a Subscription Plan.
 */
class RecurlySubscriptionPlan extends RecurlyEntity {

  /**
   * Override __construct to force an entity type.
   */
  public function __construct(array $values = [], $entityType = 'recurly_subscription_plan') {
    // We need to map to a local ID if possible. This can happen if a plan has
    // been saved locally but our entity cache is empty.
    if (!isset($values['id'])) {
      $id = entity_get_controller('recurly_subscription_plan')
        ->entityId($values['plan_code']);
      if ($id) {
        $values['id'] = $id;
      }
    }

    parent::__construct($values, $entityType);
  }

  /**
   * Override uri() for use with entity_uri().
   */
  public function uri() {
    return [
      'path' => 'recurly/subscription-plan/' . $this->id,
    ];
  }

  /**
   * Return the unit amount of this subscription plan.
   *
   * @param string $currency
   *   The currency of the subscription plan to return.
   * @param Recurly_Coupon $coupon
   *   Optional parameter of a coupon to include in the amount calculation.
   *
   * @return integer
   *   The amount of this subscription plan, in cents. This amount will always
   *   be at least 0, even if the coupon is of a greater value.
   */
  public function amount($currency = 'USD', Recurly_Coupon $coupon = NULL) {
    $amount = $this->unit_amount_in_cents->getCurrency($currency)->amount_in_cents;
    if (!$coupon) {
      return $amount;
    }

    switch ($coupon->discount_type) {
      case 'percent':
        // discount_percent is an integer.
        $amount *= (1 - $coupon->discount_percent / 100);
        break;

      case 'dollars':
        $amount -= $coupon->discount_in_cents->getCurrency($currency)->amount_in_cents;
        break;
    }

    // Also ensure that we never give a negative discount.
    return max(0, $amount);
  }
}
