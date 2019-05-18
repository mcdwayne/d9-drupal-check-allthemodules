<?php

namespace Drupal\domain_menu;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuParentFormSelector;

/**
 *
 */
class DomainMenuParentFormSelector extends MenuParentFormSelector {

  /**
   * Limits menus to ones which the current user has access to update only
   * (since this module implements different domain-based access rules for
   * menus; as opposed to core's one-permission-all-menus approach. Users
   * should only be able to add menu items and nodes to menus they have access
   * to)
   *
   * @inheritdoc
   */
  public function getParentSelectOptions($id = '', array $menus = NULL, CacheableMetadata &$cacheability = NULL) {
    $accessible_menus = [];
    if (!isset($menus)) {
      $menus = $this->getMenuOptions();
    }
    // Run access check on all menus to remove inacessible ones before letting
    // them into the normal workflow!
    foreach ($menus as $menu_id => $menu_name) {
      $menu = $this->entityManager->getStorage('menu')->load($menu_id);
      if ($menu->access('update')) {
        $accessible_menus[$menu_id] = $menu_name;
      }
    }

    return parent::getParentSelectOptions($id, $accessible_menus, $cacheability);

  }

  /**
   * @return DomainAccessManagerInterface
   */
  protected function getDomainAccessManager() {
    return \Drupal::service('domain_access.manager');
  }

  /**
   * @return AccountProxyInterface
   */
  protected function getCurrentUserAccount() {
    return \Drupal::service('current_user');
  }

}
