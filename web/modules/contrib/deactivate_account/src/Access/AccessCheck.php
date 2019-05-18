<?php

namespace Drupal\deactivate_account\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Builds an example page.
 */
class AccessCheck {

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {

    // Check permissions and combine that with any custom access checking needed. Pass forward
    // parameters from the route and/or request as needed.
    if($account->id() > 0) {
      return AccessResult::allowed();
    }
    // Return 403 Access Denied page.
    return AccessResult::forbidden();
  }
}