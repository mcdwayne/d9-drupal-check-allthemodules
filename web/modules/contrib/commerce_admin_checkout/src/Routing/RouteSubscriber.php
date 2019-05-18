<?php

namespace Drupal\commerce_admin_checkout\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('commerce_checkout.form')) {
      $route->setRequirement('_custom_access', '\Drupal\commerce_admin_checkout\CheckoutAccessHandler::checkAccess');
    }
  }
}
