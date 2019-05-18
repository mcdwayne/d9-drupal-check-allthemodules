<?php

namespace Drupal\flashpoint_community\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


/**
 * Class FlashpointCommunityRouteSubscriber
 * @package Drupal\flashpoint_community\Routing
 */
class FlashpointCommunityRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // "Enroll in Community" link
    $route = $collection->get('entity.group.join');
    $reqs = array_merge($route->getRequirements(), ['_access_flashpoint_community' => 'TRUE']);
    $route->setRequirements($reqs);
    $collection->add('entity.group.join', $route);

    // Community Management pages
    $routes['view.group_nodes.page_1'] = $collection->get('view.group_nodes.page_1');
    $routes['entity.group_content.collection'] = $collection->get('entity.group_content.collection');
    foreach ($routes as $key => $r) {
      // Check if the route exists, as gnode may not be enble for the group node route.
      if ($r) {
        $reqs = array_merge($r->getRequirements(), ['_flashpoint_community_user_role_admin' => 'TRUE']);
        $r->setRequirements($reqs);
        $collection->add($key, $r);
      }
    }
  }

}