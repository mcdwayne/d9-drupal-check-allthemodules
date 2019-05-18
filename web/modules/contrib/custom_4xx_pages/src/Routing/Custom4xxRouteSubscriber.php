<?php

namespace Drupal\custom_4xx_pages\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class Custom4xxRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change default 403 controller.
    if ($route = $collection->get('system.403')) {
      $route->setDefault('_controller', '\Drupal\custom_4xx_pages\Controller\CustomHttp4xxController:on403');
    }
    if ($route = $collection->get('system.404')) {
      $route->setDefault('_controller', '\Drupal\custom_4xx_pages\Controller\CustomHttp4xxController:on404');
    }
    if ($route = $collection->get('system.401')) {
      $route->setDefault('_controller', '\Drupal\custom_4xx_pages\Controller\CustomHttp4xxController:on401');
    }
  }

}