<?php

namespace Drupal\role_hierarchy;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Class RoleHierarchyHelper provides core functions for role_hierarchy.
 *
 * @package Drupal\role_hierarchy
 */
class RoleHierarchyHelper {

  /**
   * Get the lowest user role weight.
   */
  public static function getUserRoleWeight(User $user) {
    $roles = $user->getRoles();
    $weight = 9999;
    foreach ($roles as $role) {
      $role_weight = self::getRoleWeight($role);
      if ($role_weight < $weight) {
        $weight = $role_weight;
      }
    }
    return $weight;
  }

  /**
   * Get the lowest account role.
   */
  public static function getAccountRoleWeight(AccountInterface $account) {
    return self::getUserRoleWeight(User::load($account->id()));
  }

  /**
   * Get the weight of a role as configured in /admin/people/roles.
   *
   * @param string $role
   *   The user role.
   *
   * @return int
   *   The weight of the role.
   */
  public static function getRoleWeight($role) {
    return Role::load($role)->getWeight();
  }

}
