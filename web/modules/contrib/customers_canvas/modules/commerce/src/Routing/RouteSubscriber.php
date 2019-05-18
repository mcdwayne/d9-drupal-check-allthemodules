<?php

namespace Drupal\customers_canvas_commerce\Routing;

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
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('customers_canvas.builder')) {
      // Pull our route.
      $builder_url = \Drupal::config('customers_canvas.settings')->get('builder_url');
      $route->setDefault('_controller', '\Drupal\customers_canvas_commerce\Controller\Builder::content');
      $route->setPath("/$builder_url/{commerce_order_item}");
      $route->setOption('parameters', [
        'commerce_order_item' => ['type' => 'entity:commerce_order_item'],
      ]);
    }
  }

}
