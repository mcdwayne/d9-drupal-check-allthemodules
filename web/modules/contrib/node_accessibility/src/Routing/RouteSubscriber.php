<?php

namespace Drupal\node_accessibility\Routing;

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
    $modify_route = \Drupal::config('node_accessibility.settings')->get('alter_revision_menu');

    if ($modify_route == 1) {
      $route = $collection->get('entity.node.version_history');
      if ($route) {
        $route->addDefaults(
          array(
            '_controller' => '\Drupal\node_accessibility\Controller\NodeAccessibilityController::revisionOverview',
          )
        );
      }
    }
  }
}
