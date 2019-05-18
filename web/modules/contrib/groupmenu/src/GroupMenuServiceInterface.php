<?php

namespace Drupal\groupmenu;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\system\MenuInterface;

/**
 * Provides an interface defining a MenuAdminPerMenuAccess manager.
 */
interface GroupMenuServiceInterface {

  /**
   * A custom access check for a menu operation.
   *
   * @param string $op
   *   The operation to perform on the menu.
   * @param \Drupal\system\MenuInterface $menu
   *   Run access checks for this menu object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuAccess($op, MenuInterface $menu, AccountInterface $account = NULL);

  /**
   * Load a list of menus where a user can perform a operation.
   *
   * @param string $op
   *   The operation to perform on the menu.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the menus for.
   *
   * @return \Drupal\system\MenuInterface[]
   *   An array of menu objects keyed by menu name.
   */
  public function loadUserGroupMenus($op, AccountInterface $account = NULL);

  /**
   * Load a list of menus for a group where a user can perform a operation.
   *
   * @param string $op
   *   The operation to perform on the menu.
   * @param int $group_id
   *   The group ID to load the menus from.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\system\MenuInterface[]
   *   An array of menu objects keyed by menu name.
   */
  public function loadUserGroupMenusByGroup($op, $group_id, AccountInterface $account = NULL);

}
