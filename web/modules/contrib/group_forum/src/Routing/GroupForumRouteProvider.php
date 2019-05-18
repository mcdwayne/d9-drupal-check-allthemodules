<?php

namespace Drupal\group_forum\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_forum group content.
 */
class GroupForumRouteProvider {

  /**
   * Provides the shared collection route for group forum plugin.
   */
  public function getRoutes() {
    $routes = [];

    $routes['entity.group_content.group_forum_relate_container'] = new Route('group/{group}/forum/add');
    $routes['entity.group_content.group_forum_relate_container']
      ->setDefaults([
        '_title' => 'Relate forum container',
        '_controller' => '\Drupal\group_forum\Controller\GroupForum::addPage',
      ])
      ->setRequirement('_group_permission', 'create group_forum content')
      ->setRequirement('_group_installed_content', 'group_forum')
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
