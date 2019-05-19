<?php

namespace Drupal\simple_ldap_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class SimpleLdapUserRouteSubscriber extends RouteSubscriberBase {
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.pass')) {
      $route->setRequirement('_access', 'FALSE');
    }

    if ($route = $collection->get('user.register')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }
}
