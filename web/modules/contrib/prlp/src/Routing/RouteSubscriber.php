<?php

namespace Drupal\prlp\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for prplp routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Override controller for password reset submit action.
    if ($route = $collection->get('user.reset.login')) {
      $route->setDefault('_controller', '\Drupal\prlp\Controller\PrlpController::prlpResetPassLogin');
    }
  }

}
