<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates that there is only one active subscription for a given user.
 *
 * @Constraint(
 *   id = "OneActiveBraintreeCashierSubscription",
 *   label = @Translation("Only one active subscription constraint", context = "Validation"),
 *   type = "entity.braintree_cashier_subscription"
 * )
 */
class OneActiveBraintreeCashierSubscriptionConstraint extends CompositeConstraintBase {

  public $message = "Only one active subscription may exist at a time for each user.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['status', 'subscribed_user'];
  }

}
