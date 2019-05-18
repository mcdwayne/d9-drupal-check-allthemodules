<?php

namespace Drupal\domain_menu\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\domain_menu\Access\DomainMenuAdminAccessCheck;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters default permissions for menu administration pages.
 *
 * @see DomainMenuAdminAccessCheck
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Extend default access permissions for menu-related routes.
   * @inheritDoc
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Allow users with "administer domain menus" to access menu collection page
    // and the ajax callback route for parent menu dropdown.
    foreach ([
      'entity.menu.collection',
      'menu_ui.parent_options_js',
    ] as $route_id) {
      $route = $collection->get($route_id);
      $requirements = $route->getRequirements();
      $requirements['_permission'] = $requirements['_permission'] . "+" . "administer domain menus";
      $route->setRequirements($requirements);
    }

    $route = $collection->get('entity.menu.add_link_form');
    $route->setRequirement('_custom_access', '\Drupal\domain_menu\Access\DomainMenuAdminAccessCheck::menuLinkContentCreateAccessCheck');

    $route = $collection->get('menu_ui.link_edit');
    $requirements = $route->getRequirements();
    $requirements['_custom_access'] = '\Drupal\domain_menu\Access\DomainMenuAdminAccessCheck::menuLinkEditAccessCheck';
    $requirements['_permission'] = $requirements['_permission'] . "+" . "administer domain menus";
    $route->setRequirements($requirements);

    $route = $collection->get('menu_ui.link_reset');
    $requirements = $route->getRequirements();
    $requirements['_custom_access'] = '\Drupal\domain_menu\Access\DomainMenuAdminAccessCheck::menuLinkResetAccessCheck';
    $requirements['_permission'] = $requirements['_permission'] . "+" . "administer domain menus";
    $route->setRequirements($requirements);
  }

}
