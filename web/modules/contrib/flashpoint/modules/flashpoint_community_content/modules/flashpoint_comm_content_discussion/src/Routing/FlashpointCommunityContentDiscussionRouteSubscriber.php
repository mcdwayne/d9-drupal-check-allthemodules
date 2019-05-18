<?php

namespace Drupal\flashpoint_comm_content_discussion\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;


/**
 * Class FlashpointCommunityContentRouteSubscriber
 * @package Drupal\flashpoint_comm_content_discussion\Routing
 */
class FlashpointCommunityContentDiscussionRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // Community Management pages
    $routes['view.flashpoint_community_discussion.manage_categories'] = $collection->get('view.flashpoint_community_discussion.manage_categories');
    $routes['view.flashpoint_community_discussion.page_3'] = $collection->get('view.flashpoint_community_discussion.page_3');
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