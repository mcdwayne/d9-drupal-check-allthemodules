<?php

namespace Drupal\flashpoint_community_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;


/**
 * Class FlashpointCommunityContentRouteSubscriber
 * @package Drupal\flashpoint_community_content\Routing
 */
class FlashpointCommunityContentRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // Community Management pages
    $routes['view.flashpoint_community_manage_posts.manage_posts'] = $collection->get('view.flashpoint_community_manage_posts.manage_posts');
    foreach ($routes as $key => $r) {
      // Check if the route exists, as gnode may not be enble for the group node route.
      if ($r) {
        $reqs = array_merge($r->getRequirements(), ['_flashpoint_community_group_type' => 'TRUE']);
        $r->setRequirements($reqs);
        $collection->add($key, $r);
      }
    }
  }

}