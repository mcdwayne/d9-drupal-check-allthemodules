<?php

namespace Drupal\subscription_entity;

use Drupal\User\UserInterface;
use Drupal\subscription_entity\Entity\SubscriptionInterface;

/**
 * Defines the subscription loader interface.
 */
interface SubscriptionLoaderInterface {

  /**
   * Checks to see if the user already has a particular subscription type.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $subscription
   *   The subscription entity to check against.
   * @param \Drupal\User\UserInterface $user
   *   The user account entity.
   *
   * @return bool
   *   Returns true or false, true if the user is already assigned.
   */
  public function isUserAlreadyAssigned(SubscriptionInterface $subscription, UserInterface $user);

  /**
   * Gets the types of subscription types created within the site.
   *
   * @return mixed
   *   Usually an array.
   */
  public function getSubscriptionTypes();

  /**
   * Renew a subscription.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $subscription
   *   Subscription interface.
   */
  public function renewSubscription(SubscriptionInterface $subscription);

  /**
   * Load the subscription object.
   *
   * @param int $id
   *   The subscription id.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionInterface
   *   Subscription interface.
   */
  public function loadSubscriptionById($id);

}
