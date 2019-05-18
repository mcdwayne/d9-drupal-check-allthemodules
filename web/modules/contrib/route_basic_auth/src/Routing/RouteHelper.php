<?php

namespace Drupal\route_basic_auth\Routing;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides helper functionality for routing.
 *
 * @package Drupal\route_basic_auth\Routing
 */
class RouteHelper {

  /**
   * Returns the route name for given request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return string|null
   *   The route name.
   *   NULL if the request does not match a route.
   */
  public function getRouteNameFromRequest(Request $request) {
    $currentPath = $request->getPathInfo();
    $requestUrlObject = Url::fromUserInput($currentPath);

    if ($requestUrlObject->isRouted()) {
      $routeName = $requestUrlObject->getRouteName();
      return $routeName;
    }
    else {
      // The request does not match a route.
      return NULL;
    }
  }

}
