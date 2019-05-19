<?php

namespace Drupal\user_edit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on login status of current user.
 */
class UserEditAccessCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Symfony\Component\Routing\Route $route
   *
   * @return $this
   */
  public function access(AccountInterface $account, Route $route) {
    $required_status = filter_var($route->getRequirement('_user_edit'), FILTER_VALIDATE_BOOLEAN);
    $actual_status = $account->isAuthenticated() && User::load($account->id())
        ->access('update', $account, TRUE)
        ->isAllowed();
    $access_result = AccessResult::allowedIf($required_status === $actual_status)
      ->addCacheContexts(['user.roles:authenticated']);

    // Checks whether a user is allowed to proceed with the specified event.
    if (!$access_result->isAllowed()) {
      $access_result->setReason($required_status === TRUE ?
        'This route can only be accessed by authenticated users.' :
        'This route can only be accessed by anonymous users.');
    }

    return $access_result;
  }
}
