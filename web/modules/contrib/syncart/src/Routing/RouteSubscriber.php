<?php

namespace Drupal\syncart\Routing;

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
    if ($route = $collection->get('commerce_cart.page')) {
      $route->setPath('/commerce_cart');
      $route->setRequirement('_permission', 'administer site configuration');
    }
    if ($route = $collection->get('syncart.cart_controller_cart')) {
      $route->setPath('/cart');
    }
  }

}
