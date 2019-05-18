<?php

namespace Drupal\dblog_pager\Routing;

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
    // Change controller for dblog event view to our controller.
    if ($route = $collection->get('dblog.event')) {
      $route->setDefault('_controller', '\Drupal\dblog_pager\Controller\DblogPagerController::evDetails');
    }
  }

}
