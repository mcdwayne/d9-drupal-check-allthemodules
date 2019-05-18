<?php

namespace Drupal\core_extend\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a trait for declaring a status form route.
 */
trait StatusFormRouteTrait {

  use EntityTypeIdKeyTypeTrait;

  /**
   * Gets the status-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getStatusFormRoute(EntityTypeInterface $entity_type) {
    if ($route = new Route($entity_type->getLinkTemplate('status-form'))) {
      $entity_type_id = $entity_type->id();

      $route_defaults = $route->getDefaults();
      $route_defaults['_entity_form'] = "{$entity_type_id}.status";
      $route_defaults['_entity_type_id'] = $entity_type_id;
      $route_defaults['_title_callback'] = '\Drupal\core_extend\Controller\EntityController::statusTitle';

      $route_options = $route->getOptions();
      $route_options['_admin_route'] = TRUE;
      $route_options['parameters'][$entity_type_id]['type'] = 'entity:' . $entity_type_id;

      $route_requirements = $route->getRequirements();
      $route_requirements['_entity_access'] = "{$entity_type_id}.update";

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }

      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options)
        ->setRequirements($route_requirements);

      return $route;
    }
  }

}
