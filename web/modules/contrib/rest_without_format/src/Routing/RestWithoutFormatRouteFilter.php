<?php

namespace Drupal\rest_without_format\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RouteFilterInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Overrides the functionality of RequestFormatRouteFilter.
 */
class RestWithoutFormatRouteFilter implements RouteFilterInterface {

  /**
   * The route filter.
   *
   * @var \Drupal\Core\Routing\RouteFilterInterface
   */
  protected $requestFormatRouteFilter;

  /**
   * Rest resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RestWithoutFormatRouteFilter constructor.
   * @param \Drupal\Core\Routing\RouteFilterInterface $request_format_route_filter
   *   The route filter.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_plugin_manager
   *   The rest plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(RouteFilterInterface $request_format_route_filter, ResourcePluginManager $resource_plugin_manager, ModuleHandlerInterface $module_handler) {
    $this->requestFormatRouteFilter = $request_format_route_filter;
    $this->resourcePluginManager = $resource_plugin_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement('_format');
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Get all rest plugins.
    $rest_plugins = $this->resourcePluginManager->getDefinitions();

    $endpoints = [];

    // Prepare list of all rest plugin's canonical.
    foreach ($rest_plugins as $rest_plugin) {
      $endpoints[] = $rest_plugin['uri_paths']['canonical'];
    }

    // If no rest resource , use original router filter.
    if (empty($endpoints)) {
      return $this->requestFormatRouteFilter->filter($collection, $request);
    }

    $format = $request->getRequestFormat('html');

    // Allows module to alter route collection if required.
    $this->moduleHandler->alter('rest_without_format', $collection, $request, $endpoints);

    $rest_routes = [];

    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $name => $route) {
      // Get route path.
      $route_path = $route->getPath();

      // If this route is of rest type.
      if (in_array(substr($route_path, 1), $endpoints)) {
        // Get the route format.
        $route_format = $route->getRequirement('_format');

        // If route and request format are not same, means we don't have _format
        // key in endpoint.
        if ($route_format != $format) {
          /* @todo Check if route supports the given format*/
          $route->setRequirement('_format', 'xml');
          $rest_routes[] = $route_path;
        }
      }

    }

    // If there is any route found.
    if (count($rest_routes)) {
      return $collection;
    }

    // If nothing found, use original route filter behavior.
    return $this->requestFormatRouteFilter->filter($collection, $request);
  }

}
