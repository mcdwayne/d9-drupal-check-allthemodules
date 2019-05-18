<?php

namespace Drupal\route_basic_auth\Config;

/**
 * Configuration for a route that should be protected.
 *
 * @package Drupal\route_basic_auth\Config
 */
class ProtectedRouteConfig {

  /**
   * The route name.
   *
   * @var string
   */
  private $name;

  /**
   * Array of HTTP methods that should be protected for route.
   *
   * @var array[string]
   */
  private $methods;

  /**
   * ProtectedRouteConfig constructor.
   *
   * @param string $name
   *   The route name.
   * @param array $methods
   *   The HTTP methods.
   */
  public function __construct($name, array $methods) {
    $this->name = $name;
    $this->methods = $methods;
  }

  /**
   * Checks if given method of route should be protected.
   *
   * @param string $method
   *   The HTTP method that should be checked.
   */
  public function shouldMethodBeProtected($method) {
    if (in_array($method, $this->getMethods())) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Gets the route name.
   *
   * @return string
   *   The route name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the methods.
   *
   * @return array
   *   The HTTP methods.
   */
  public function getMethods() {
    return $this->methods;
  }

}
