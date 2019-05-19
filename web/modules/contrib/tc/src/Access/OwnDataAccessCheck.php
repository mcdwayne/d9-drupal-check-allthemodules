<?php

/**
 * @file
 * Contains \Drupal\tc\Access\OwnDataAccessCheck.
 */

namespace Drupal\tc\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;

/**
 * Checks access for displaying configuration translation page.
 */
class OwnDataAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $routeMatch
   *   Run access checks against the account provided by this router match.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access is allowed if the id of the two accounts are the same.
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch) {
    $access = AccessResult::allowedIf($routeMatch && $routeMatch->getRawParameter('user') == $account->id());
    $access->addCacheContexts(['user']);
    return $access;
  }

}
