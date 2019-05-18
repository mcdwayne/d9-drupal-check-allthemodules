<?php

namespace Drupal\braintree_cashier\Plugin\Validation\Constraint;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a user has at most one active subscription.
 */
class OneActiveBraintreeCashierSubscriptionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * Subscription storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * BraintreeCashierOneActiveSubscriptionConstraintValidator constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $subscription_storage
   *   The subscription storage.
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
    if ($entity->getStatus() == BraintreeCashierSubscriptionInterface::ACTIVE) {
      $query = $this->subscriptionStorage->getQuery();
      $query->condition('subscribed_user.target_id', $entity->subscribed_user->target_id)
        ->condition('status', BraintreeCashierSubscriptionInterface::ACTIVE)
        // The id could be NULL, so we cast it to 0 in that case.
        ->condition('id', (int) $entity->id(), '<>')
        ->range(0, 1)
        ->count();
      $another_exists = (bool) $query->execute();
      if ($another_exists) {
        $this->context->buildViolation($constraint->message)
          ->atPath('status')
          ->addViolation();
      }
    }
  }

}
