<?php

namespace Drupal\route_path_rewrite\Config;

/**
 * Configuration for a route that should be rewritten.
 *
 * @package Drupal\route_path_rewrite\Config
 */
class RouteRewriteConfig {

  /**
   * The route machine name.
   *
   * @var string
   */
  private $name;

  /**
   * The new path for the route.
   *
   * @var string
   */
  private $newPath;

  /**
   * RouteRewriteConfig constructor.
   *
   * @param string $name
   *   The route name.
   * @param string $newPath
   *   The new path for the route.
   */
  public function __construct($name, $newPath) {
    $this->name = $name;
    $this->newPath = $newPath;
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
   * Gets the new route path.
   *
   * @return string
   *   The new path for the route.
   */
  public function getNewPath() {
    return $this->newPath;
  }

}
