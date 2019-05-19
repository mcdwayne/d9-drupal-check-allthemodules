<?php

namespace Drupal\toolshed_menu\MenuResolver;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Interface for creating different strategies to resolving menu links.
 *
 * Provides an interface for defining different classes that can resolve
 * menu links from different contexts and constraints. This will allow for
 * navigation menus that can use different resolvers to create custom
 * menu trees.
 */
interface MenuResolverInterface extends PluginInspectionInterface {

  /**
   * A list of caching tags that apply to this menu resolver.
   *
   * @param string[] $menuNames
   *   Either a single menu name or an array of menu names to search
   *   for the menu link in.
   * @param Drupal\Core\Routing\RouteMatchInterface|null $route
   *   The route information to use when determining which menu content
   *   link to return when attempting to resolve the menu link to return.
   *   Will default to the current path if parameter is NULL.
   *
   * @return string[]
   *   An array of caching tags to use.
   */
  public function getCacheTags(array $menuNames = [], RouteMatchInterface $route = NULL);

  /**
   * A list of caching contexts that apply to this menu resolver.
   *
   * @param string[] $menuNames
   *   Either a single menu name or an array of menu names to search
   *   for the menu link in.
   * @param Drupal\Core\Routing\RouteMatchInterface|null $route
   *   The route information to use when determining which menu content
   *   link to return when attempting to resolve the menu link to return.
   *   Will default to the current path if parameter is NULL.
   *
   * @return string[]
   *   An array of caching contexts to use.
   */
  public function getCacheContexts(array $menuNames = [], RouteMatchInterface $route = NULL);

  /**
   * Based on the URI and menu name contraints, resolve the menu link.
   *
   * @param string[] $menuNames
   *   Either a single menu name or an array of menu names to search
   *   for the menu link in.
   * @param Drupal\Core\Routing\RouteMatchInterface|null $route
   *   The route information to use when determining which menu content
   *   link to return when attempting to resolve the menu link to return.
   *   Will default to the current path if parameter is NULL.
   *
   * @return array
   *   Returns a menu link determined by this menu resolver based on
   *   the constraints of $menuName and routing information. If a matching
   *   menu link information can't be found, an emoty array will be returned.
   */
  public function resolve(array $menuNames = [], RouteMatchInterface $route = NULL);

}
