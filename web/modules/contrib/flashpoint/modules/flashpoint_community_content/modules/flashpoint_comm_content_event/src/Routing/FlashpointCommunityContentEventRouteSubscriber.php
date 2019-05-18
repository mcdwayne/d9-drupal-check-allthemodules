<?php

namespace Drupal\flashpoint_comm_content_event\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;


/**
 * Class FlashpointCommunityContentEventRouteSubscriber
 * @package Drupal\flashpoint_comm_content_event\Routing
 */
class FlashpointCommunityContentEventRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // Community Management pages
    $routes['view.flashpoint_community_events_in_community.manage_events'] = $collection->get('view.flashpoint_community_events_in_community.manage_events');
    $routes['view.flashpoint_community_events_in_community.page_1'] = $collection->get('view.flashpoint_community_events_in_community.page_1');
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