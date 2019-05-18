<?php

namespace Drupal\discussions\Routing;

use Drupal\discussions\Entity\DiscussionType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group Discussions.
 */
class GroupDiscussionRouteProvider {

  /**
   * Provides the route for adding a group Discussion.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (DiscussionType::loadMultiple() as $name => $discussion_type) {
      $plugin_id = "group_discussion:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_create[] = "create $name discussion";
    }

    // Return routes here if there are no Discussion types.
    if (empty($plugin_ids)) {
      return $routes;
    }

    // Add Discussion.
    $routes['entity.group_content.group_discussion_add'] = new Route('group/{group}/discussion/create');
    $routes['entity.group_content.group_discussion_add']
      ->setDefaults([
        '_title' => 'Create Discussion',
        '_controller' => '\Drupal\discussions\Controller\GroupDiscussionController::add',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
