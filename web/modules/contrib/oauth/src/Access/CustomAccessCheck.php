<?php
/**
 * @file
 * Contains \Drupal\example\Access\CustomAccessCheck.
 */

namespace Drupal\oauth\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\UserInterface;

/**
 * Checks access for oauth.
 */
class CustomAccessCheck implements AccessInterface {

  /**
   * Check if the user can administer their own keys, or has the 'administer
   * consumer' permission.
   *
   * @param \Drupal\Core\User\UserInterface
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @return bool
   */

  public function access(UserInterface $user, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer consumers')
      ->orIf(
        AccessResult::allowedIf($user->id() == $account->id())->addCacheableDependency($account)->
        andIf(AccessResult::allowedIfHasPermission($account, 'access own consumers')));
  }
}
