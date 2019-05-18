<?php

namespace Drupal\filebrowser\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\filebrowser\Services\Common;

/**
 * Checks access to filebrowser page.
 */
class FilebrowserAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param RouteMatchInterface $route_match
   * @return AccessResult
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    if ($op = $route_match->getParameter('op')) {
      if ($permission = static::mapActionToPermission($op)) {
        return AccessResult::allowedIfHasPermission($account, $permission);
      }
    }
    return AccessResult::neutral();
  }

  protected static function mapActionToPermission($action) {
    $permissions = [
      'delete' => Common::DELETE_FILES,
      'description' => Common::RENAME_FILES,
      'folder' => Common::CREATE_FOLDER,
      'upload' => Common::FILE_UPLOAD,
      'rename' => Common::RENAME_FILES,
      'archive' => Common::DOWNLOAD_ARCHIVE,
    ];
    return isset($permissions[$action]) ? $permissions[$action] : NULL;
  }
}

