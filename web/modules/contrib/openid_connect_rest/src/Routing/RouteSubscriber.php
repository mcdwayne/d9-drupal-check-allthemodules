<?php

namespace Drupal\openid_connect_rest\Routing;

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
    // Change path '/openid-connect/{client_name}'
    // to '/openid-connect/rest/{provider_id}'.
    if ($route_to_alter = $collection->get('openid_connect.redirect_controller_redirect')) {
      $route = $collection->get('openid_connect_rest.api.authenticate');

      $route_to_alter->setPath($route->getPath());
      $route_to_alter->setMethods($route->getMethods());
      $route_to_alter->setDefaults($route->getDefaults());
      $route_to_alter->setRequirements($route->getRequirements());
    }
  }

}
