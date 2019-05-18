<?php

namespace Drupal\role_hierarchy\Plugin\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\role_hierarchy\RoleHierarchyHelper;
use Drupal\user\Entity\User;

/**
 * Class used to set custom access on role_hierarchy functionality.
 */
class RoleHierarchyAccess {

  /**
   * Users can only edit with an equal or lower hierarchical role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of the user performing the edit.
   * @param \Drupal\user\Entity\User $user
   *   The user which is being edited.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The account is allowed/forbidden to edit an user.
   */
  public function accessEditUser(AccountInterface $account, User $user) {
    $account_weight = RoleHierarchyHelper::getAccountRoleWeight($account);
    $user_weight = RoleHierarchyHelper::getUserRoleWeight($user);
    return AccessResult::allowedIf($account_weight <= $user_weight);
  }

}
