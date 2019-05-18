<?php

namespace Drupal\groupmenu\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_menu group content.
 */
class GroupMenuRouteProvider {

  /**
   * Provides the shared collection route for group menu plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    $plugin_id = "group_menu:menu";
    $plugin_ids[] = $plugin_id;
    $permissions_add[] = "create $plugin_id content";
    $permissions_create[] = "create $plugin_id entity";

    $routes['entity.group_content.group_menu_relate_page'] = new Route('group/{group}/menu/add');
    $routes['entity.group_content.group_menu_relate_page']
      ->setDefaults([
        '_title' => 'Relate menu',
        '_controller' => '\Drupal\groupmenu\Controller\GroupMenuController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_menu_add_page'] = new Route('group/{group}/menu/create');
    $routes['entity.group_content.group_menu_add_page']
      ->setDefaults([
        '_title' => 'Create menu',
        '_controller' => '\Drupal\groupmenu\Controller\GroupMenuController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
