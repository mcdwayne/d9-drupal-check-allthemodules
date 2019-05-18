<?php

namespace Drupal\private_messages\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for displaying private messages.
 */
class PrivateMessagesAccessCheck implements AccessInterface
{
  /**
   * Access check for Private messages routes.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(AccountInterface $account)
  {
    // Allow everything from admin.
    if ($account->hasPermission('administer private messages')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Forbid access to anyone who has no permissions for messaging. No further checking needed.
    if (!$account->hasPermission('use private messages')) {
      return AccessResult::forbidden();
    }

    // Allow access to messages only for current account only.
    if (\Drupal::request()->get('user')->id() == $account->id()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }
}
