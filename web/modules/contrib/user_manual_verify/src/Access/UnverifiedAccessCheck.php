<?php

namespace Drupal\user_manual_verify\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;

/**
 * Checks that user is not verified.
 */
class UnverifiedAccessCheck implements AccessInterface {

  /**
   * Check to see if user accessed this page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatch $match) {

    /** @var \Drupal\user\Entity\User $user */
    $user = $match->getParameter('user');

    if (empty($user->getLastAccessedTime())) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    else {
      return AccessResult::forbidden()->setCacheMaxAge(-1);
    }
  }

}
