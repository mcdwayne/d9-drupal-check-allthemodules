<?php

namespace Drupal\groupmenu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\system\MenuInterface;

/**
 * Checks access for displaying menu pages.
 */
class GroupMenuAccess implements GroupMenuAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function menuEditAccess(AccountInterface $account, MenuInterface $menu) {
    return \Drupal::service('groupmenu.menu')->menuAccess('update', $menu, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function menuDeleteAccess(AccountInterface $account, MenuInterface $menu) {
    return \Drupal::service('groupmenu.menu')->menuAccess('delete', $menu, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function menuItemAccess(AccountInterface $account, MenuLinkContentInterface $menu_link_content = NULL) {
    $menus = \Drupal::service('groupmenu.menu')->loadUserGroupMenus('edit', $account);
    if ($account->hasPermission('administer menu') || !empty($menus)) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function menuLinkAccess(AccountInterface $account, MenuLinkInterface $menu_link_plugin = NULL) {
    $menus = \Drupal::service('groupmenu.menu')->loadUserGroupMenus('edit', $account);
    if ($account->hasPermission('administer menu') || !empty($menus)) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
