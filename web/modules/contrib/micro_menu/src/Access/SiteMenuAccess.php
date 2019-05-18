<?php

namespace Drupal\micro_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\system\MenuInterface;
use Symfony\Component\Routing\Route;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\micro_site\SiteUsers;

/**
 * Provides an access checker for site entities menu edit form.
 */
class SiteMenuAccess {

  /**
   * Checks access to the entity operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\system\MenuInterface $menu
   *   The menu on which check access.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, MenuInterface $menu, SiteInterface $site = NULL) {
    $site_id = $menu->getThirdPartySetting('micro_menu', 'site_id');

    if (empty($site_id)) {
      return AccessResult::forbidden('Menu is not associated with a site entity')->addCacheableDependency($menu);
    }

    if (empty($site)) {
      // Try to load it form it's id stored on the menu.
      $site = Site::load($site_id);
      if (empty($site)) {
        return AccessResult::forbidden('Site associated with the menu not exists no more')->addCacheableDependency($menu);
      }
    }

    if (!$site->hasMenu()) {
      return AccessResult::forbidden('The site entity is not configured to have a menu')->addCacheableDependency($site);
    }

    if ($site->id() != $site_id) {
      return AccessResult::forbidden('Menu do not correspond to the site id')->addCacheableDependency($menu);
    }

    if (!$site->isRegistered() && !$account->hasPermission('administer micro menus')) {
      return AccessResult::forbidden('Menu can be managed only on site registered and so from the site url.')->addCacheableDependency($menu)->addCacheableDependency($site);
    }

    if ($account->hasPermission('administer micro menus')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission('administer own micro menu')) {
      // Site administrators and owner can always manage their menu
      if ($account->id() == $site->getOwnerId() || in_array($account->id(), $site->getUsersId(SiteUsers::MICRO_SITE_ADMINISTRATOR))) {
        return AccessResult::allowed()->addCacheableDependency($site)->cachePerPermissions();
      }

      // Site manager can manage the site menu.
      if (in_array($account->id(), $site->getUsersId(SiteUsers::MICRO_SITE_MANAGER)) ) {
        return AccessResult::allowed()->addCacheableDependency($site)->cachePerPermissions();
      }
    }

    // No opinion, let's others module give access eventually.
    return AccessResult::neutral();
  }

}
