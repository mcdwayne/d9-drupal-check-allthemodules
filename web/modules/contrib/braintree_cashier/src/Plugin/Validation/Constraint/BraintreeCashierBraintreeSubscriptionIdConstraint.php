<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates that the period end date is set when cancel at period end is true.
 *
 * @Constraint(
 *   id = "BraintreeCashierBraintreeSubscriptionId",
 *   label = @Translation("Braintree Subscription ID constraint", context = "Validation"),
 *   type = "entity:braintree_cashier_subscription"
 * )
 */
class BraintreeCashierBraintreeSubscriptionIdConstraint extends CompositeConstraintBase {

  public $message = "The Braintree subscription ID field must be filled for the selected subscription type.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['braintree_subscription_id', 'subscription_type'];
  }

}
