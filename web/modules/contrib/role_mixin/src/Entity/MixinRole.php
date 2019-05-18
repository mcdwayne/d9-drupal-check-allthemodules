<?php

namespace Drupal\role_mixin\Entity;

use Drupal\user\Entity\Role;

/**
 * Overrides the role entity class to support mixin roles.
 */
class MixinRole extends Role {

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $stored_permissions = parent::getPermissions();

    // Stop early to not cause an infinite loop.
    // This also blocks you from having more than one level of testing, which is
    // like in OOP the usecase of a mixin/trait.
    if ($this->getThirdPartySetting('role_mixin', 'parent_roles')) {
      return $stored_permissions;
    }

    return array_merge($stored_permissions, $this->getMixinRolePermissions()->getPermissionsOfParentRole($this->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    if ($stored_permission_result =  parent::hasPermission($permission)) {
      return $stored_permission_result;
    }

    return in_array($permission, $this->getMixinRolePermissions()->getPermissionsOfParentRole($this->id()));
  }

  /**
   * @return \Drupal\role_mixin\MixinRolePermissions
   */
  protected function getMixinRolePermissions() {
    return \Drupal::service('role_mixin.mixin_role_permissions');
  }

}
