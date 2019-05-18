<?php

namespace Drupal\admin_feedback\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    $admin_routes = ['view.feedback.nodes_list', 'view.feedback.nodes_score'];
    foreach ($collection->all() as $name => $route) {
      if (in_array($name, $admin_routes)) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }
}
