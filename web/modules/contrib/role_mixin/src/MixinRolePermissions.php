<?php

namespace Drupal\role_mixin;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class MixinRolePermissions {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Creates a new MixinRolePermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->roleStorage = $entity_type_manager->getStorage('user_role');
  }

  public function getPermissionsOfParentRole($name) {
    $permissions_by_parent = $this->getPermissionsByParentRole();
    return isset($permissions_by_parent[$name]) ? $permissions_by_parent[$name] : [];
  }

  protected function getPermissionsByParentRole() {
    $roles = $this->roleStorage->loadMultiple();

    $permissions_by_parent = [];
    /** @var \Drupal\user\Entity\Role[] $roles */
    foreach ($roles as $role_id => $role) {
      if ($parent_roles = $role->getThirdPartySetting('role_mixin', 'parent_roles')) {
        foreach ($parent_roles as $parent_role) {
          $permissions_by_parent += [$parent_role => []];
          $permissions_by_parent[$parent_role] = array_merge($roles[$role_id]->getPermissions(), $permissions_by_parent[$parent_role]);
        }
      }
    }

    return $permissions_by_parent;
  }

}
