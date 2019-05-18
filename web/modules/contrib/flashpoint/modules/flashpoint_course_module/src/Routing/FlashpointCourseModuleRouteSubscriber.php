<?php

namespace Drupal\flashpoint_course_module\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


/**
 * Listens to the dynamic route events.
 */
class FlashpointCourseModuleRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // Course Management pages
    $routes['view.flashpoint_manage_course_modules_in_course.page_1'] = $collection->get('view.flashpoint_manage_course_modules_in_course.page_1');
    foreach ($routes as $key => $r) {
      $reqs = array_merge($r->getRequirements(), ['_flashpoint_course_group_type' => 'TRUE']);
      $r->setRequirements($reqs);
      $collection->add($key, $r);
    }
  }

}