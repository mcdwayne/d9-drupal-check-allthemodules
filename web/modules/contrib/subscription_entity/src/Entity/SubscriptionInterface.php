<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Subscription entities.
 *
 * @ingroup subscription
 */
interface SubscriptionInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Subscription type.
   *
   * @return string
   *   The Subscription type.
   */
  public function getType();

  /**
   * Gets the Subscription id.
   *
   * @return string
   *   Id of the Subscription.
   */
  public function getSubscriptionRef();

  /**
   * Sets the Subscription ref.
   *
   * @param string $ref
   *   The Subscription ref.
   *
   * @return \Drupal\subscription_entity\Entity\subscriptionInterface
   *   The called Subscription entity.
   */
  public function setSubscriptionRef($ref);

  /**
   * Gets the Subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the Subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Subscription creation timestamp.
   *
   * @return \Drupal\subscription_entity\Entity\subscriptionInterface
   *   The called Subscription entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Subscription published status indicator.
   *
   * Unpublished Subscription are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Subscription is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Subscription.
   *
   * @param bool $published
   *   TRUE to set this Subscription to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\subscription_entity\Entity\subscriptionInterface
   *   The called Subscription entity.
   */
  public function setPublished($published);

  /**
   * Gets the Subscription revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Subscription revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\subscription_entity\Entity\subscriptionInterface
   *   The called Subscription entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Subscription revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Subscription revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\subscription_entity\Entity\subscriptionInterface
   *   The called Subscription entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Checks to see if the subscription is active or not.
   *
   * @return bool
   *   Whether or not the subscription is active.
   */
  public function isActive();

  /**
   * Badly named function but basically this gets the remaining time left.
   *
   * @param int $numberOfMonthsToReduceOffTheEndDate
   *   Number which reduces the number of months
   *   from the end date of a subscription term.
   *
   * @return mixed
   *   Number of days or NULL if the current date is
   *   not in between the end date and the end date minus the number of months.
   */
  public function getTimeLeftByRemainingMonths($numberOfMonthsToReduceOffTheEndDate);

  /**
   * Get the subscription owner.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function getSubscriptionOwner();

  /**
   * Sets the subscription owner.
   *
   * If the subscription is active and a user has been added to the subscription
   * then trigger the necessary events.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return subscriptionInterface
   *   This.
   */
  public function setSubscriptionOwner(UserInterface $user);

  /**
   * Activates the subscription.
   */
  public function activateSubscription();

  /**
   * DeActivates the subscription.
   */
  public function deActivateSubscription();

  /**
   * Cancels the subscription.
   */
  public function cancelSubscription();

  /**
   * Checks to see if a user is already assigned a subscription.
   *
   * This uses the subscription loader method.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return bool
   *   TRUE if the user is already assigned FALSE otherwise.
   */
  public function isUserAlreadyAssigned(UserInterface $user);

  /**
   * If we have one of more term then we can class this a renewal.
   *
   * @return bool
   *   TRUE if its a renewal FALSE otherwise.
   */
  public function isRenewal();

  /**
   * Get a list of term ids.
   *
   * @return array|int
   *   An array of term ids.
   */
  public function getTermIds();

  /**
   * Gets a subscription's latest term.
   *
   * @return array
   *   The latest term object in an array.
   */
  public function getLatestTerm();

  /**
   * Renews a subscription.
   */
  public function renew();

  /**
   * Gets the subscription type entity.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionTypeInterface
   *   SubscriptionTypeInterface
   */
  public function getSubscriptionTypeEntity();

}
