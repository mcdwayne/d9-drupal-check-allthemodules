<?php

namespace Drupal\commerce_inventory\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\core_extend\EventSubscriber\ViewsRouteTrait;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route changes for the commerce inventory module.
 */
class CommerceInventoryRouteSubscriber extends RouteSubscriberBase {

  use ViewsRouteTrait;

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Inventory Location routes.
    $entity_type_location = \Drupal::entityTypeManager()->getDefinition('commerce_inventory_location');
    $entity_type_location_id = $entity_type_location->id();

    if ($inventory_location_adjustments_route = $this->getInventoryLocationAdjustmentsRoute($entity_type_location, $collection)) {
      $collection->add("entity.{$entity_type_location_id}.inventory_adjustments", $inventory_location_adjustments_route);
    }
    if ($inventory_location_inventory_route = $this->getInventoryLocationInventoryRoute($entity_type_location, $collection)) {
      $collection->add("entity.{$entity_type_location_id}.inventory", $inventory_location_inventory_route);
    }

    // Inventory Item routes.
    $entity_type_item = \Drupal::entityTypeManager()->getDefinition('commerce_inventory_item');
    $entity_type_item_id = $entity_type_item->id();

    if ($inventory_item_adjustments_route = $this->getInventoryItemAdjustmentsRoute($entity_type_item, $collection)) {
      $collection->add("entity.{$entity_type_item_id}.adjustments", $inventory_item_adjustments_route);
    }

  }

  /**
   * Gets the Inventory Location - Adjustments route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The current route collection.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryLocationAdjustmentsRoute(EntityTypeInterface $entity_type, RouteCollection $collection) {
    $view_route_name = 'view.commerce_inventory_adjustments.admin_location';
    $entity_route_name = 'inventory-adjustments';

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

  /**
   * Gets the Inventory Location - Inventory route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The current route collection.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryLocationInventoryRoute(EntityTypeInterface $entity_type, RouteCollection $collection) {
    $view_route_name = 'view.commerce_inventory_commerce_inventory_item.admin_location';
    $entity_route_name = 'inventory';

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
      // $route->getRequirements();
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

  /**
   * Gets the Inventory Item - Adjustments route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The current route collection.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryItemAdjustmentsRoute(EntityTypeInterface $entity_type, RouteCollection $collection) {
    $view_route_name = 'view.commerce_inventory_adjustments.admin_item';
    $entity_route_name = 'adjustments';

    if ($route = $this->createFromViewsRoute($entity_route_name, $view_route_name, $entity_type, $collection)) {
      $entity_type_id = $entity_type->id();

      // Set route defaults.
      $route_defaults = $route->getDefaults();
      $route_defaults['_entity'] = $entity_type_id;

      // Set route options.
      $route_options = $route->getOptions();
      $route_options['_admin_route'] = TRUE;
      $route_options['parameters'][$entity_type_id]['type'] = 'entity:' . $entity_type_id;
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';

      // Set route requirements.
      $route_requirements = $route->getRequirements();
      $route_requirements['_location_owns_entity'] = 'commerce_inventory_item';
      $route_requirements['_entity_access'] = 'commerce_inventory_item.modify';
      $route_requirements[$entity_type_id] = '\d+';
      $route_requirements['commerce_inventory_location'] = '\d+';

      // Add configuration to route.
      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options)
        ->setRequirements($route_requirements);

      return $route;
    }
  }

}
