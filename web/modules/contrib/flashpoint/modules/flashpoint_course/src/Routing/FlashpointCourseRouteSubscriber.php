<?php

namespace Drupal\flashpoint_course\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


/**
 * Listens to the dynamic trousers route events.
 */
class FlashpointCourseRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // "Enroll in Course" link
    $route = $collection->get('entity.group.join');
    $reqs = array_merge($route->getRequirements(), ['_access_flashpoint_course' => 'TRUE']);
    $route->setRequirements($reqs);
    $collection->add('entity.group.join', $route);

    // Course Management pages
    $routes['view.group_nodes.page_1'] = $collection->get('view.group_nodes.page_1');
    $routes['entity.group_content.collection'] = $collection->get('entity.group_content.collection');
    foreach ($routes as $key => $r) {
      if ($r) {
        $reqs = array_merge($r->getRequirements(), ['_flashpoint_course_user_role_admin' => 'TRUE']);
        $r->setRequirements($reqs);
        $collection->add($key, $r);
      }
    }
  }

}