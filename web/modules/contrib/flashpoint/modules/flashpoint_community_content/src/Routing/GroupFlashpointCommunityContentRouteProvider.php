<?php

namespace Drupal\flashpoint_community_content\Routing;

use Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_flashpoint_community_content group content.
 */
class GroupFlashpointCommunityContentRouteProvider {

  /**
   * Provides the shared collection route for group flashpoint_community_content plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (FlashpointCommunityContentType::loadMultiple() as $name => $flashpoint_community_c_type) {
      $plugin_id = "group_flashpoint_community_content:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no flashpoint_community_content types yet, we cannot have any plugin IDs and should
    // therefore exit early because we cannot have any routes for them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_content.group_flashpoint_community_content_relate_page'] = new Route('group/{group}/flashpoint_community_content/add');
    $routes['entity.group_content.group_flashpoint_community_content_relate_page']
      ->setDefaults([
        '_title' => 'Relate Community Content',
        '_controller' => '\Drupal\flashpoint_community_content\Controller\GroupFlashpointCommunityContentController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_flashpoint_community_content_add_page'] = new Route('group/{group}/flashpoint_community_content/create');
    $routes['entity.group_content.group_flashpoint_community_content_add_page']
      ->setDefaults([
        '_title' => 'Create Community Content',
        '_controller' => '\Drupal\flashpoint_community_content\Controller\GroupFlashpointCommunityContentController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
