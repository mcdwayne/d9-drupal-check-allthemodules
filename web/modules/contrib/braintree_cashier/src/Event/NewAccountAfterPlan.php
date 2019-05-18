<?php

namespace Drupal\braintree_cashier\Event;

use Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Creates an event when a new user account is created after selecting a plan.
 */
class NewAccountAfterPlan extends Event {

  /**
   * The billing plan selected.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $billingPlan;

  /**
   * The user account created.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * NewAccountAfterPlan constructor.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billingPlan
   *   The billing plan entity selected.
   * @param \Drupal\user\Entity\User $account
   *   The user account created.
   */
  public function __construct(BraintreeCashierBillingPlanInterface $billingPlan, User $account) {
    $this->billingPlan = $billingPlan;
    $this->account = $account;
  }

  /**
   * Gets the billing plan.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   *   The billing plan selected.
   */
  public function getBillingPlan() {
    return $this->billingPlan;
  }

  /**
   * Gets the user account created.
   *
   * @return \Drupal\user\Entity\User
   *   The user account created.
   */
  public function getAccount() {
    return $this->account;
  }

}
