<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Ensure that the period end date is not empty.
 *
 * The period end date should not be empty if the subscription will cancel at
 * period end and it is of type FREE.
 */
class BraintreeCashierPeriodEndDateNotEmptyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * Subscription entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * Constructs a new PeriodEndDateNotEmptyConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $subscription_storage
   *   The subscription entity storage handler.
   */
  public function __construct(EntityStorageInterface $subscription_storage) {
    $this->subscriptionStorage = $subscription_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager')->getStorage('braintree_cashier_subscription'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $entity */
    $will_cancel_at_period_end = $entity->willCancelAtPeriodEnd();
    $period_end_date_is_set = !empty($entity->getPeriodEndDate());
    $is_free_type = $entity->getSubscriptionType() === BraintreeCashierSubscriptionInterface::FREE;

    if ($will_cancel_at_period_end && !$period_end_date_is_set && $is_free_type) {
      $this->context->buildViolation($constraint->message)
        ->atPath('period_end_date')
        ->addViolation();
    }
  }

}
