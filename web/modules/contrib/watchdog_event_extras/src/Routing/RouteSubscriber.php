<?php

namespace Drupal\watchdog_event_extras\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * RouteSubscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('dblog.event')) {
      $route->setDefault('_controller', '\Drupal\watchdog_event_extras\Controller\WatchdogEventExtrasDbLogController::eventDetails');
    }
  }

}
