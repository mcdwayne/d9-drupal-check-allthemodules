<?php

/**
 * @file
 * Provides Drupal\themekey\Engine\EngineInterface
 */

namespace Drupal\themekey;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for ThemeKey Engines.
 */
interface EngineInterface {

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   Returns the active theme name, else return NULL.
   */
  public function determineTheme(RouteMatchInterface $route_match);

  /**
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  public function getRouteMatch();

  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  public function getConfigFactory();

}
