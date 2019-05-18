<?php

namespace Drupal\groupmenu\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\system\MenuInterface;

/**
 * Provides an interface defining a MenuAdminPerMenuAccess manager.
 */
interface GroupMenuAccessInterface {

  /**
   * A custom access check for menu page and add link page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\system\MenuInterface $menu
   *   Run access checks for this menu object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuEditAccess(AccountInterface $account, MenuInterface $menu);

  /**
   * A custom access check for menu delete page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\system\MenuInterface $menu
   *   Run access checks for this menu object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuDeleteAccess(AccountInterface $account, MenuInterface $menu);

  /**
   * A custom access check for menu items page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content
   *   Run access checks for this menu item object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuItemAccess(AccountInterface $account, MenuLinkContentInterface $menu_link_content = NULL);

  /**
   * A custom access check for menu link page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin
   *   Run access checks for this menu link object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuLinkAccess(AccountInterface $account, MenuLinkInterface $menu_link_plugin = NULL);

}
