<?php

namespace Drupal\flashpoint_course_module\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for flashpoint_course_module group content.
 */
class FlashpointCourseGroupFlashpointCourseModuleRouteProvider {

  /**
   * Provides the shared collection route for flashpoint_course_module plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    $plugin_id = "flashpoint_course_module";

    $permissions_add = "add flashpoint_course_module content";
    $permissions_create = "create flashpoint_course_module content";

    $routes['entity.group_content.flashpoint_course_module_relate_page'] = new Route('group/{group}/flashpoint_course_module/add');
    $routes['entity.group_content.flashpoint_course_module_relate_page']
      ->setDefaults([
        '_title' => 'Relate Resource',
        '_controller' => '\Drupal\flashpoint_course_module\Controller\FlashpointCourseGroupFlashpointCourseModuleController::addPage',
      ])
      ->setRequirement('_group_permission', $permissions_add)
      ->setRequirement('_group_installed_content', $plugin_id)
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.flashpoint_course_module_add_page'] = new Route('group/{group}/flashpoint_course_module/create');
    $routes['entity.group_content.flashpoint_course_module_add_page']
      ->setDefaults([
        '_title' => 'Create Course Module',
        '_controller' => '\Drupal\flashpoint_course_module\Controller\FlashpointCourseGroupFlashpointCourseModuleController::addPage',
//        '_form' => '\Drupal\flashpoint_course_module\Form\FlashpointCourseGroupFlashpointCourseModuleForm',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', $permissions_create)
      ->setRequirement('_group_installed_content', $plugin_id)
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
