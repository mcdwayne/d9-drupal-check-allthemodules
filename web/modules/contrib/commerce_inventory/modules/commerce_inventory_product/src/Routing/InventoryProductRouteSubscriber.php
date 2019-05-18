<?php

namespace Drupal\commerce_inventory_product\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\core_extend\EventSubscriber\ViewsRouteTrait;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route changes for the commerce inventory product module.
 */
class InventoryProductRouteSubscriber extends RouteSubscriberBase {

  use ViewsRouteTrait;

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Inventory Location routes.
    $entity_type_location = \Drupal::entityTypeManager()->getDefinition('commerce_inventory_location');
    $entity_type_location_id = $entity_type_location->id();

    if ($inventory_location_add_product_route = $this->getInventoryLocationAddProductRoute($entity_type_location, $collection)) {
      $collection->add("entity.{$entity_type_location_id}.inventory_add_product", $inventory_location_add_product_route);
    }
  }

  /**
   * Gets the Inventory Location - Add Product route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The current route collection.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryLocationAddProductRoute(EntityTypeInterface $entity_type, RouteCollection $collection) {
    $view_route_name = 'view.commerce_inventory_location_add_product.inventory_add_product';
    $entity_route_name = 'inventory-add-product';

    if ($route = $this->createFromViewsRoute($entity_route_name, $view_route_name, $entity_type, $collection)) {
      $entity_type_id = $entity_type->id();

      // Set route defaults.
      $route_defaults = $route->getDefaults();
      $route_defaults['_entity'] = $entity_type_id;

      // Set route options.
      $route_options = $route->getOptions();
      $route_options['_admin_route'] = TRUE;
      $route_options['parameters'][$entity_type_id]['type'] = 'entity:' . $entity_type_id;

      // Set route requirements.
      $route_requirements = $route->getRequirements();
      $route_requirements['_entity_access'] = 'commerce_inventory_location.inventory_modify';
      $route_requirements[$entity_type_id] = '\d+';

      // Add configuration to route.
      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options)
        ->setRequirements($route_requirements);

      return $route;
    }
  }

}
