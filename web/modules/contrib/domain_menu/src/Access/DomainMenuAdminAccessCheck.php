<?php

namespace Drupal\domain_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\system\MenuInterface;

/**
 * Container for custom-access callbacks for displaying Menu administration pages.
 */
class DomainMenuAdminAccessCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\system\MenuInterface $menu
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   */
  public function menuLinkContentCreateAccessCheck(AccountInterface $account, MenuInterface $menu) {
    return $menu->access('update', $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function menuLinkEditAccessCheck(AccountInterface $account, MenuLinkInterface $menu_link_plugin) {
    /** @var MenuInterface $menu */
    $menu = $this->getMenuStorage()->load($menu_link_plugin->getMenuName());
    return $menu->access('update', $account, TRUE);
  }

  /**
   *
   */
  public function menuLinkResetAccessCheck(AccountInterface $account, MenuLinkInterface $menu_link_plugin) {
    /** @var MenuInterface $menu */
    $menu = $this->getMenuStorage()->load($menu_link_plugin->getMenuName());
    return AccessResult::allowedIf($menu->access('update', $account) && $menu_link_plugin->isResettable());
  }

  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getMenuStorage() {
    return \Drupal::entityTypeManager()->getStorage('menu');
  }

}
