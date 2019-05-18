<?php

namespace Drupal\loopit\Routing;

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

    if ($route = $collection->get('devel.container_info.service.detail')) {
      $route->setDefault('_controller', 'Drupal\loopit\Controller\LoopitServiceController::serviceDetail');
    }

  }
}