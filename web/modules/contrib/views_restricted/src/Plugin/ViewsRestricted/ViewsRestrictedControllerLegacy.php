<?php

namespace Drupal\views_restricted\Plugin\ViewsRestricted;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\views\ViewEntityInterface;
use Drupal\views_restricted\ViewsRestrictedPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Example plugin implementation of the views_restricted.
 *
 * @ViewsRestricted(
 *   id = "views_restricted_legacy",
 * )
 */
class ViewsRestrictedControllerLegacy extends ViewsRestrictedPluginBase {

  public function getAccess(ViewEntityInterface $view, $display_id = NULL, $type = NULL, $table = NULL, $field = NULL, $alias = NULL, Route $route = NULL, RouteMatch $route_match = NULL) {
    if ($route && $route_match) {
      $accessResult = AccessResult::allowed();
      // Most or all forms don't need this as they suppress caching.
      $accessResult->addCacheableDependency($view);
      if ($decoratedEntityAccess = $route_match->getParameter('_views_restricted_decorated_entity_access')) {
        $accessResult->andIf($view->access($decoratedEntityAccess, NULL, TRUE));
      }
      if ($decoratedPermission = $route_match->getParameter('_views_restricted_decorated_permission')) {
        $accessResult->andIf(AccessResult::allowedIfHasPermission(\Drupal::currentUser(), $decoratedPermission));
      }
      return $accessResult;
    }
    else {
      return AccessResult::allowed();
    }
  }

}
