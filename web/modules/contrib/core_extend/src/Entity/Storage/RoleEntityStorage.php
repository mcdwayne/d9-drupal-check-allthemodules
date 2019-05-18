<?php

namespace Drupal\core_extend\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Controller class for role entity storage classes.
 */
class RoleEntityStorage extends ConfigEntityStorage implements RoleEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function isPermissionInRoles($permission, array $rids) {
    $has_permission = FALSE;
    foreach ($this->loadMultiple($rids) as $role) {
      /** @var \Drupal\core_extend\Entity\RoleEntityInterface $role */
      if ($role->isAdmin() || $role->hasPermission($permission)) {
        $has_permission = TRUE;
        break;
      }
    }

    return $has_permission;
  }

}
