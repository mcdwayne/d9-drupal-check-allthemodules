<?php

namespace Drupal\monster_menus\Menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkInterface;
use Symfony\Component\Routing\Route;

/**
 * Overrides DefaultMenuLinkTreeManipulators to allow for menu links which
 * contain unspecified {mm_tree} parameters.
 */
class MenuLinkTreeManipulators extends DefaultMenuLinkTreeManipulators {

  /**
   * Checks access for one menu link instance. We need to override this because
   * menus can refer to paths containing {mm_tree}, which might not be supplied
   * as a parameter.
   *
   * @param MenuLinkInterface $instance
   *   The menu link instance.
   *
   * @return AccessResultInterface
   *   The access result.
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    $access_result = NULL;
    if ($this->account->hasPermission('link to any page')) {
      $access_result = AccessResult::allowed();
    }
    else {
      $url = $instance->getUrlObject();

      // When no route name is specified, this must be an external link.
      if (!$url->isRouted()) {
        $access_result = AccessResult::allowed();
      }
      else {
        /** @var Route $route */
        $route = \Drupal::service('router.route_provider')->getRouteByName($url->getRouteName());
        // If {mm_tree} is present in the path but not supplied, use the current
        // page or the homepage.
        if (isset($route->getOption('parameters')['mm_tree']) && !isset($url->getRouteParameters()['mm_tree'])) {
          mm_parse_args($mmtids, $oarg_list, $this_mmtid);
          $url->setRouteParameter('mm_tree', $this_mmtid ?: mm_home_mmtid());
        }
        $access_result = $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $this->account, TRUE);
      }
    }
    return $access_result->cachePerPermissions();
  }

}
