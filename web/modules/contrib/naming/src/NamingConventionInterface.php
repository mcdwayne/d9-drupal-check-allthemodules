<?php

/**
 * @file
 * Contains \Drupal\naming\NamingConventionInterface.
 */

namespace Drupal\naming;

use Drupal\Core\Routing\RouteMatchInterface;
/**
 * Provides an interface defining a NamingConvention.
 */
interface NamingConventionInterface {

  /**
   * Returns the NamingConvention path.
   *
   * @return array
   *   The NamingConvention path.
   */
  public function getPath();

  /**
   * Returns the NamingConvention content.
   *
   * @return array
   *   The NamingConvention content.
   */
  public function getContent();

  /**
   * Returns the NamingConvention category.
   *
   * @return array
   *   The NamingConvention category.
   */
  public function getCategory();

  /**
   * Returns the NamingConvention weight.
   *
   * @return array
   *   The NamingConvention weight.
   */
  public function getWeight();

  /**
   * Returns a URL object for the naming convention's route (aka id).
   *
   * @return \Drupal\Core\Url|false
   *   The url object, or FALSE if the route is not valid.
   */
  public function getRouteUrl();

  /**
   * Returns a URL object for the naming convention's path.
   *
   * @return \Drupal\Core\Url|false
   *   The url object, or FALSE if the path is not valid.
   */
  public function getPathUrl();

  /**
   * Load naming convention using route march.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A route match.
   *
   * @return \Drupal\naming\NamingConventionInterface|NULL
   *   A naming convention
   */
  public static function loadFromRouteMatch(RouteMatchInterface $route_match);

}
