<?php

namespace Drupal\otl_logout\Routing;

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
    // Change reset-password route to use our custom controller.
    if ($route = $collection->get('user.reset')) {
      // Change the route to use the overridden callback.
      $route->setDefault('_controller', '\Drupal\otl_logout\OtlLogoutController::resetPassLogin');

      // Change the requirements check. Note: the requirements can't be left
      // empty, so use the "access content" permission as anonymous visitors are
      // usually able to access it too.
      $route->setRequirements(['_permission' => 'access content']);
    }
  }

}
