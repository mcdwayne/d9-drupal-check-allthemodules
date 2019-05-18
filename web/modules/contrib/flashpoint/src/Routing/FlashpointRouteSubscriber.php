<?php

namespace Drupal\flashpoint\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


/**
 * Listens to the dynamic trousers route events.
 */
class FlashpointRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    // Find the route we want to alter
    $route = $collection->get('entity.group.join');

    $defs = $route->getDefaults();
    $defs['_controller'] = '\Drupal\flashpoint\FlashpointUtilities::groupJoinForm';
    $defs['_title_callback'] = '\Drupal\flashpoint\FlashpointUtilities::groupJoinTitle';
    $route->setDefaults($defs);
    // Re-add the collection to override the existing route.
    $collection->add('entity.group.join', $route);
  }

}