<?php

namespace Drupal\integro\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for connector authorization.
 *
 * @see \Drupal\Core\Access\CustomAccessCheck
 */
class ConnectorAuthAccessCheck {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::allowedIfHasPermissions($account, [
      'administer integro_connector',
    ]);

    // @todo Extend access check for checking connectors by ownership.

    return $result;
  }

}
