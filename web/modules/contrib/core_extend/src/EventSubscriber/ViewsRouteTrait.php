<?php

namespace Drupal\core_extend\EventSubscriber;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a trait for base config entities.
 */
trait ViewsRouteTrait {

  /**
   * Converts a views route to an entity route.
   *
   * @param string $entity_route_name
   *   The entity route name to create or override.
   * @param string $view_route_name
   *   The view route to use.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The current route collection.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function createFromViewsRoute($entity_route_name, $view_route_name, EntityTypeInterface $entity_type, RouteCollection $collection) {
    $view_route = $collection->get($view_route_name);
    if ($view_route && $entity_type->hasLinkTemplate($entity_route_name) && $route = new Route($entity_type->getLinkTemplate($entity_route_name))) {
      // Set route defaults.
      $route_defaults = $route->getDefaults();
      $route_defaults['_controller'] = $view_route->getDefault('_controller');
      $route_defaults['_view_display_show_admin_links'] = $view_route->getDefault('_view_display_show_admin_links');
      $route_defaults['display_id'] = $view_route->getDefault('display_id');
      $route_defaults['view_id'] = $view_route->getDefault('view_id');

      // Set route options.
      $route_options = $route->getOptions();
      $route_options['_view_argument_map'] = $view_route->getOption('_view_argument_map');
      $route_options['_view_display_plugin_class'] = $view_route->getOption('_view_display_plugin_class');
      $route_options['_view_display_plugin_id'] = $view_route->getOption('_view_display_plugin_id');
      $route_options['_view_display_show_admin_links'] = $view_route->getOption('_view_display_show_admin_links');
      $route_options['returns_response'] = $view_route->getOption('returns_response');

      // Add configuration to route.
      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options);

      // Remove original view route.
      $collection->remove($view_route_name);

      return $route;
    }
  }

}
