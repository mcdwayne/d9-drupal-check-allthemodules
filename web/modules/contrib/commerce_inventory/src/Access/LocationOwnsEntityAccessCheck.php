<?php

namespace Drupal\commerce_inventory\Access;

use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Checks access to entity based on if route Inventory Location is owner.
 *
 * Determines access to routes based on whether an entity belongs
 * to the location that was also specified in the route.
 */
class LocationOwnsEntityAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {

    $parameter_id = $route->getRequirement('_location_owns_entity');

    // Don't interfere if no location or entity was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('commerce_inventory_location') || !$parameters->has($parameter_id)) {
      return AccessResult::neutral();
    }

    // Don't interfere if the location isn't a real location.
    $location = $parameters->get('commerce_inventory_location');
    if (!$location instanceof InventoryLocationInterface) {
      return AccessResult::neutral();
    }

    // Don't interfere if the entity doesn't implement 'getLocationId()'.
    $entity = $parameters->get($parameter_id);
    if (!method_exists($entity, 'getLocationId')) {
      return AccessResult::neutral();
    }

    // If we have a location and entity, see if the owner matches.
    $location_owns_entity = $entity->getLocationId() == $location->id();

    // Throw 404 if parent-child relationship doesn't exist.
    if (!$location_owns_entity) {
      throw new ResourceNotFoundException();
    }

    // Allow access if the entity is owned by the location.
    return AccessResult::allowed();
  }

}
