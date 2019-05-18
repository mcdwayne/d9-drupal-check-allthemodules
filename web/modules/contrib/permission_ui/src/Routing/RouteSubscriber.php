<?php

namespace Drupal\permission_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // As nodes are the primary type of content, the node listing should be
    // easily available. In order to do that, override admin/content to show
    // a node listing instead of the path's child links.
    $route = $collection->get('block_content.add_form');
    if ($route) {
      $requirements = $route->getRequirements();
      if (isset($requirements['_permission'])) {
        // @todo load permission_ui permission and add to entity routes.
        // For now works for back block add permission.
        $requirements['_permission'] .= '+add_any_basic_block_content';
      }
      $route->setRequirements($requirements);
    }
  }

}
