<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Braintree Cashier subscription entities.
 *
 * @ingroup braintree_cashier
 */
interface BraintreeCashierSubscriptionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Subscription statuses.
   */
  const ACTIVE = 'active';
  const CANCELED = 'canceled';

  /**
   * Subscription types.
   */
  const FREE = 'free';
  const PAID_INDIVIDUAL = 'paid_individual';

  /**
   * Gets a list of subscription types that require a Braintree subscription ID.
   *
   * @return array
   *   An array of subscription type machine names.
   */
  public static function getSubscriptionTypesNeedBraintreeId();

  /**
   * Gets the subscription status.
   *
   * @return string
   *   The subscription status.
   */
  public function getStatus();

  /**
   * Gets the billing plan from which this subscription was generated.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   *   The billing plan.
   */
  public function getBillingPlan();

  /**
   * Get the discounts associated with the subscription.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemList
   *   The list of Discount entity references.
   */
  public function getDiscounts();

  /**
   * Gets the Braintree Subscription ID.
   *
   * @return string
   *   The Braintree Subscription ID.
   */
  public function getBraintreeSubscriptionId();

  /**
   * Sets the Braintree subscription ID.
   *
   * @param string $braintree_subscription_id
   *   The Braintree subscription ID.
   */
  public function setBraintreeSubscriptionId($braintree_subscription_id);

  /**
   * Gets the subscription type value.
   *
   * @return string
   *   The value of the subscription type.
   */
  public function getSubscriptionType();

  /**
   * Gets the Subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Subscription.
   */
  public function getCreatedTime();

  /**
   * Gets whether the subscription will cancel at the end of the current period.
   *
   * @return bool
   *   A boolean indicating whether the subscription will cancel as period end.
   */
  public function willCancelAtPeriodEnd();

  /**
   * Gets the current period end date.
   *
   * @return int
   *   The UNIX timestamp representing the end date of the current billing
   *   period.
   */
  public function getPeriodEndDate();

  /**
   * Sets the period end date.
   *
   * @param int $timestamp
   *   The UNIX timestamp representing the end date of the current billing
   *   period.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setPeriodEndDate($timestamp);

  /**
   * Gets the free trial start date.
   *
   * @return int
   *   The UNIX timestamp representing the date when the free trial began.
   */
  public function getTrialStartDate();

  /**
   * Sets the free trial start date.
   *
   * @param int $timestamp
   *   The UNIX timestamp representing when the free trial began.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setTrialStartDate($timestamp);

  /**
   * Gets whether the subscription began with a free trial.
   *
   * @return bool
   *   A boolean representing whether the subscription began with a free trial.
   */
  public function beganWithFreeTrial();

  /**
   * Gets the free trial end date.
   *
   * @return int
   *   The UNIX timestamp representing the date when the free trial began.
   */
  public function getTrialEndDate();

  /**
   * Sets the free trial end date.
   *
   * The free trial end date is set only if the subscription charged
   * successfully and began with a free trial.
   *
   * @param int $timestamp
   *   The UNIX timestamp representing when the free trial ended.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setTrialEndDate($timestamp);

  /**
   * Gets the ended at date.
   *
   * @return int
   *   The UNIX timestamp representing the date when the subscription ended.
   */
  public function getEndedAtDate();

  /**
   * Sets the ended at date.
   *
   * @param int $timestamp
   *   The UNIX timestamp representing the date when the subscription ended.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscriptino entity.
   */
  public function setEndedAtDate($timestamp);

  /**
   * Gets the date when the subscription was canceled.
   *
   * Normally this is the date when billing for the subscription was canceled,
   * leaving the subscription still active until the end of the billing period.
   *
   * @return int
   *   The UNIX timestamp representing the date when the subscription was
   *   canceled.
   */
  public function getCanceledAtDate();

  /**
   * Sets the canceled at date.
   *
   * @param int $timestamp
   *   The UNIX timestamp representing the date when the subscription was
   *   canceled.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setCanceledAtDate($timestamp);

  /**
   * Gets the user ID of the subscribed user.
   *
   * @return int
   *   The user ID of the subscribed user.
   */
  public function getSubscribedUserId();

  /**
   * Gets the roles to assign to the subscribed user.
   *
   * @return array
   *   A list of role ID's.
   */
  public function getRolesToAssign();

  /**
   * Gets the roles to revoke from the subscribed user.
   *
   * @return array
   *   A list of role ID's.
   */
  public function getRolesToRevoke();

  /**
   * Sets the Subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Subscription creation timestamp.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The called Subscription entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Subscription published status indicator.
   *
   * Unpublished Subscription are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the subscription is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a subscription.
   *
   * @param bool $published
   *   TRUE to set this Subscription to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The called Subscription entity.
   */
  public function setPublished($published);

  /**
   * Sets the status of the subscription.
   *
   * @param string $status
   *   The subscription status.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setStatus($status);

  /**
   * Gets the subscribed user.
   *
   * @return \Drupal\user\UserInterface
   *   The subscribed user entity.
   */
  public function getSubscribedUser();

  /**
   * Sets the cancel message.
   *
   * @param string $message
   *   The cancel message provided by the user when they canceled.
   */
  public function setCancelMessage($message);

  /**
   * Set cancel at period end.
   *
   * @param bool $will_cancel
   *   A boolean indicating whether the subscription will cancel at period end.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setCancelAtPeriodEnd($will_cancel);

  /**
   * Gets the name of the subscription.
   *
   * @return string
   *   The name of the subscription.
   */
  public function getName();

  /**
   * Sets the name of the subscription.
   *
   * @param string $name
   *   The name of the subscription.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setName($name);

  /**
   * Sets the billing plan entity reference.
   *
   * @param string $billing_plan_id
   *   The entity ID of the billing plan.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setBillingPlan($billing_plan_id);

  /**
   * Sets the roles to assign when the subscription becomes active.
   *
   * @param array $roles_to_assign
   *   A list of role ID's.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setRolesToAssign(array $roles_to_assign);

  /**
   * Sets the roles to revoke when the subscription is canceled.
   *
   * @param array $roles_to_revoke
   *   A list of role ID's.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setRolesToRevoke(array $roles_to_revoke);

  /**
   * Sets the subscription type.
   *
   * @param string $type
   *   The subscription type.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setType($type);

  /**
   * Gets whether the subscription is on a free trial managed by Braintree.
   *
   * @return bool
   *   A boolean indicating whether the subscription is on a free trial.
   */
  public function isTrialing();

  /**
   * Sets whether the subscription is on a free trial managed by Braintree.
   *
   * @param bool $is_trialing
   *   A boolean indicating whether the subscription is on a free trial.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   */
  public function setIsTrialing($is_trialing);

}
