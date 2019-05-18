<?php

namespace Drupal\message_thread_history\Routing;

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
    // Change controller for message_history.
    if ($route = $collection->get('message_history.read')) {
      $route->setDefault('_controller', '\Drupal\message_thread_history\Controller\HistoryController::read');
    }
  }

}
