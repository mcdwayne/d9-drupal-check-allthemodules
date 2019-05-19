<?php

namespace Drupal\user_badges\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the access control handler for the badge listing.
 */
class BadgeAccessController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $badge_admin = $account->hasPermission('administer badge entities');

    if ($badge_admin) {
      return AccessResult::allowed();
    }
    else {
      $user_from_url = \Drupal::routeMatch()->getParameter('user');
      return AccessResult::allowedIf($account->id() && $account->id() == $user_from_url->id() && $account->hasPermission('re-order badges'))
        ->cachePerPermissions()
        ->cachePerUser();
    }
  }
}
