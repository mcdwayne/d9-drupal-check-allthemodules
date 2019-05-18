<?php

namespace Drupal\permission_ui;

use Drupal\permission_ui\Entity\Permission;

/**
 * Defines a class for dynamic permissions from the UI.
 *
 * @internal
 */
class Permissions {

  /**
   * Generate permission array from the UI.
   *
   * @return array
   *   An array of permissions.
   */
  public function generatePermissions() {
    $permissions = [];
    /** @var \Drupal\permission_ui\Entity\Permission $permission */
    foreach (Permission::loadMultiple() as $id => $permission) {
      $permissions[$id] = $permission->toPermissionApi();
    }
    return $permissions;
  }

}
