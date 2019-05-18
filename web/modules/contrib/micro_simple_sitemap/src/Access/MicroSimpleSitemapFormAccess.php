<?php

namespace Drupal\micro_simple_sitemap\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Provides an access checker for micro site XML sitemap settings form.
 */
class MicroSimpleSitemapFormAccess {

  /**
   * Checks access to the entity operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, SiteInterface $site = NULL) {
    if (!$site instanceof SiteInterface) {
      return AccessResult::forbidden('Site associated with the sitemap not exists');
    }

    if (!$site->isRegistered()) {
      return AccessResult::forbidden('Sitemap XML settings can be managed only on site registered and so from the site url.')->addCacheableDependency($site);
    }

    if ($account->hasPermission('administer micro sites xml sitemap')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission('administer own micro site xml sitemap')) {
      $admin_user = $site->getAdminUsersId();
      if (in_array($account->id(), $admin_user)) {
        return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }

    // No opinion, let's others module give access eventually.
    return AccessResult::neutral();
  }

}
