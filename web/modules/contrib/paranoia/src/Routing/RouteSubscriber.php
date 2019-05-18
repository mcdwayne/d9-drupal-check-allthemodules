<?php

namespace Drupal\paranoia\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $hidden_routes = \Drupal::moduleHandler()->invokeAll('paranoia_hide_routes');
    foreach ($hidden_routes as $key) {
      if ($route = $collection->get($key)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
