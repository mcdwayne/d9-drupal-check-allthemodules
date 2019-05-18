<?php

namespace Drupal\hide_all\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $settings = \Drupal::configFactory()->get('hide_all.settings');
    $route_names = $settings->get('route_names');
    if (!empty($route_names) && is_array($route_names)) {
      foreach ($collection->all() as $name => $route) {
        if (in_array($name, $route_names, TRUE)) {
          $route->setRequirement('_access', 'FALSE');
          continue;
        }
      }
    }
  }

}
