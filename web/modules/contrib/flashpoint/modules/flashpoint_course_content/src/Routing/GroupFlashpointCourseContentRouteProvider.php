<?php

namespace Drupal\flashpoint_course_content\Routing;

use Drupal\flashpoint_course_content\Entity\FlashpointCourseContentType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_flashpoint_course_content group content.
 */
class GroupFlashpointCourseContentRouteProvider {

  /**
   * Provides the shared collection route for group flashpoint_course_content plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (FlashpointCourseContentType::loadMultiple() as $name => $flashpoint_course_content_type) {
      $plugin_id = "group_flashpoint_course_content:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no flashpoint_course_content types yet, we cannot have any plugin IDs and should
    // therefore exit early because we cannot have any routes for them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_content.group_flashpoint_course_content_relate_page'] = new Route('group/{group}/flashpoint_course_content/add');
    $routes['entity.group_content.group_flashpoint_course_content_relate_page']
      ->setDefaults([
        '_title' => 'Relate Course Content',
        '_controller' => '\Drupal\flashpoint_course_content\Controller\GroupFlashpointCourseContentController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_flashpoint_course_content_add_page'] = new Route('group/{group}/flashpoint_course_content/create');
    $routes['entity.group_content.group_flashpoint_course_content_add_page']
      ->setDefaults([
        '_title' => 'Create Course Content',
        '_controller' => '\Drupal\flashpoint_course_content\Controller\GroupFlashpointCourseContentController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
