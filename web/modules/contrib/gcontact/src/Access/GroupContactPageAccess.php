<?php

/**
 * @file
 * Contains Drupal\gcontact\Access\GroupContactPageAccess.
 */

namespace Drupal\gcontact\Access;

use Drupal\contact\Access\ContactPageAccess;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Access check for contact_personal_page route.
 */
class GroupContactPageAccess extends ContactPageAccess implements AccessInterface {

  /**
   * Checks access to the given user's contact page based on group membership.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being contacted.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    $contact_account = $user;
    /** @var \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader */
    $group_membership_loader = \Drupal::service('group.membership_loader');

    // By default, users should not see their own contact forms.
    if (($account->id() !== $contact_account->id()) &&
      // Group membership applies only if both users are authenticated.
      $account->isAuthenticated() &&
      $contact_account->isAuthenticated() &&
      ($account_memberships = $group_membership_loader->loadByUser($contact_account)) &&
      ($contact_memberships = $group_membership_loader->loadByUser($account))) {
      foreach ($contact_memberships as $contact_membership) {
        // Determine if the currently logged in user is a member of the group.
        foreach ($account_memberships as $account_membership) {
          if ($contact_membership->getGroup()->id() == $account_membership->getGroup()->id() &&
            $account_membership->hasPermission('access member contact forms')) {
            return AccessResult::allowed()
              ->cachePerUser()
              ->addCacheableDependency($contact_membership)
              ->addCacheableDependency($account_membership);
          }
        }
      }
    }

    return parent::access($user, $account);
  }

}

