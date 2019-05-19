<?php

namespace Drupal\yasm_charts\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class YasmChartsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Override the controllers with an extended version of it.
    if ($route = $collection->get('yasm.statistics.site.contents')) {
      $route->setDefault('_controller', 'Drupal\yasm_charts\Controller\Contents::siteContent');
    }
    if ($route = $collection->get('yasm.statistics.my.contents')) {
      $route->setDefault('_controller', 'Drupal\yasm_charts\Controller\Contents::myContent');
    }
    if ($route = $collection->get('yasm.statistics.site.users')) {
      $route->setDefault('_controller', 'Drupal\yasm_charts\Controller\Users::siteContent');
    }
    if ($route = $collection->get('yasm.statistics.site.files')) {
      $route->setDefault('_controller', 'Drupal\yasm_charts\Controller\Files::siteContent');
    }
    if ($route = $collection->get('yasm.statistics.site.groups')) {
      $route->setDefault('_controller', 'Drupal\yasm_charts\Controller\Groups::siteContent');
    }
    if ($route = $collection->get('yasm.statistics.my.groups')) {
      $route->setDefault('_controller', 'Drupal\yasm_charts\Controller\Groups::myContent');
    }
  }

}
