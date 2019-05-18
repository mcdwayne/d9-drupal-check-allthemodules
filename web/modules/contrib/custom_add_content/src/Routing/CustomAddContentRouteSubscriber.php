<?php

namespace Drupal\custom_add_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class CustomAddContentRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change path '/node/add' to '/custom_node_add'.
    if ($route = $collection->get('node.add_page')) {
      $route->setPath('/custom_node_add');
    }
  }

}
