<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Ensure that the billing plan has the subscription ID field populated.
 *
 * Whether the subscription ID needs to be populated depends on the subscription
 * type created by the billing plan.
 */
class BraintreeCashierBraintreeSubscriptionIdConstraintValidator extends ConstraintValidator {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $entity */
    $needs_braintree_subscription_id = in_array($entity->getSubscriptionType(), BraintreeCashierSubscription::getSubscriptionTypesNeedBraintreeId());
    if ($needs_braintree_subscription_id && empty($entity->getBraintreeSubscriptionId())) {
      $this->context->buildViolation($constraint->message)
        ->atPath('braintree_subscription_id')
        ->addViolation();
    }
  }

}
