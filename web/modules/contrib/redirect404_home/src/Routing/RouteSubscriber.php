<?php

namespace Drupal\redirect404_home\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Override "system.404".
    if ($route = $collection->get('system.404')) {
      $route->setDefaults([
        '_controller' => '\Drupal\redirect404_home\Controller\Redirect404Home::on404',
      ]);
    }
  }

}
