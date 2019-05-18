<?php

namespace Drupal\private_page\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for private page.
 */
class PrivatePageAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    $page = current(\Drupal::entityTypeManager()
      ->getStorage('private_page')
      ->loadByProperties(['private_path' => \Drupal::request()->getRequestUri()]));

    if ($page && $page->getPrivatePagePath()) {
      foreach ($page->getPermissions() as $permission) {
        return AccessResult::allowedIf($account->hasPermission($permission));
      }
    }

    return AccessResult::allowed();
  }

}
