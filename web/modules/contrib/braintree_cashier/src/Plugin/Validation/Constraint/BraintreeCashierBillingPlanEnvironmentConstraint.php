<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates only one billing plan per Braintree plan ID per environment.
 *
 * @Constraint(
 *   id = "BraintreeCashierBillingPlanEnvironment",
 *   label = @Translation("Billing Plan Environment constraint", context = "Validation"),
 *   type = "entity:braintree_cashier_billing_plan",
 * )
 */
class BraintreeCashierBillingPlanEnvironmentConstraint extends CompositeConstraintBase {

  public $message = "There is already a billing plan entity with the Braintree plan id %plan_id in the %environment environment";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['braintree_plan_id', 'environment'];
  }

}
