<?php

namespace Drupal\micro_path\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Custom access control handler for the micro path overview page.
 */
class MicroPathListCheck {

  /**
   * Handles route permissions on the micro path list page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account making the route request.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function viewMicroPathList(AccountInterface $account) {
    if ($account->hasPermission('administer micro path')
      || $account->hasPermission('view micro path list')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
