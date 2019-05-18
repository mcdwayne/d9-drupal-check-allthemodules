<?php

namespace Drupal\mailing_list;

use Drupal\mailing_list\SubscriptionInterface;

/**
 * Interface definition for the mailing list manager service.
 */
interface MailingListManagerInterface {

  /**
   * Grants the current user access to a subscription for the current session.
   *
   * @param \Drupal\mailing_list\SubscriptionInterface\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function grantSessionAccess(SubscriptionInterface $subscription);

  /**
   * Revoke any session access to a subscription to the current user.
   *
   * @param \Drupal\mailing_list\SubscriptionInterface\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function revokeSessionAccess(SubscriptionInterface $subscription);

  /**
   * Checks if the current user has session access to a given subscription.
   *
   * @param \Drupal\mailing_list\SubscriptionInterface\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return bool
   *   TRUE if the current user has session access granted to the subscription.
   */
  public function hasSessionAccess(SubscriptionInterface $subscription);

}
