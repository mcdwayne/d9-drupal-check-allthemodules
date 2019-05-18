<?php

namespace Drupal\hold_my_draft\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\hold_my_draft\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Override node's route for revision overview so we can modify it.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route list.
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.node.version_history');

    if ($route) {
      $route->setDefault('_controller', '\Drupal\hold_my_draft\Controller\NodeController::revisionOverview');
    }
  }

}
