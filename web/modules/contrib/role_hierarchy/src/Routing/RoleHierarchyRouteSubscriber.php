<?php

namespace Drupal\role_hierarchy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RoleHierarchyRouteSubscriber plugin subscribes to intercept routes.
 *
 * @package Drupal\role_hierarchy\Routing
 */
class RoleHierarchyRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (['entity.user.cancel_form', 'entity.user.edit_form'] as $user_route) {
      if ($route = $collection->get($user_route)) {
        $route->setRequirement('_custom_access', 'Drupal\role_hierarchy\Plugin\Access\RoleHierarchyAccess::accessEditUser');
      }
    }
  }

}
