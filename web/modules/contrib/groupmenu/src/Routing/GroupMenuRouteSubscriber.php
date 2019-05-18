<?php

namespace Drupal\groupmenu\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modify form for config.sync route.
 */
class GroupMenuRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {

        case 'entity.menu.edit_form':
        case 'entity.menu.add_link_form':
          $route->setRequirements(['_custom_access' => '\Drupal\groupmenu\Access\GroupMenuAccess::menuEditAccess']);
          break;

        case 'entity.menu.delete_form':
          $route->setRequirements(['_custom_access' => '\Drupal\groupmenu\Access\GroupMenuAccess::menuDeleteAccess']);
          break;

        case 'menu_ui.link_edit':
        case 'menu_ui.link_reset':
          $route->setRequirements(['_custom_access' => '\Drupal\groupmenu\Access\GroupMenuAccess::menuLinkAccess']);
          break;

        case 'entity.menu_link_content.canonical':
        case 'entity.menu_link_content.delete_form':
          $route->setRequirements(['_custom_access' => '\Drupal\groupmenu\Access\GroupMenuAccess::menuItemAccess']);
          break;

      }
    }
  }

}
