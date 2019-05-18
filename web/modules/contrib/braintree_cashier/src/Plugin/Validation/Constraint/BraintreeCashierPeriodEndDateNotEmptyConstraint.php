<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates that the period end date is set when cancel at period end is true.
 *
 * @Constraint(
 *   id ="BraintreeCashierPeriodEndDateNotEmpty",
 *   label = @Translation("Period end date constraint", context = "Validation"),
 *   type = "entity:braintree_cashier_subscription"
 * )
 */
class BraintreeCashierPeriodEndDateNotEmptyConstraint extends CompositeConstraintBase {

  public $message = "The period end date must be set if the subscription will cancel at period end";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['period_end_date', 'cancel_at_period_end', 'subscription_type'];
  }

}
