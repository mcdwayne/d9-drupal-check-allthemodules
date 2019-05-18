<?php

namespace Drupal\back_office_access_restriction\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Blocks access to given routes.
 *
 * @package Drupal\back_office_access_restriction\Routing
 */
class RoutesAlter extends RouteSubscriberBase {

  /**
   * Defining $routes.
   *
   * @var array
   */
  protected $routes;

  /**
   * Constructs a new RoutesAlter object.
   *
   * @param array $routes
   *   An array of routes.
   */
  public function __construct(array $routes) {
    $this->routes = $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    foreach ($this->routes as $route_name) {
      $route = $collection->get($route_name);
      $route->setRequirements(['_access' => 'false']);
    }
  }

}
