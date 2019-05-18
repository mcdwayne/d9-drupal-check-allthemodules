<?php

namespace Drupal\core_extend\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a trait for declaring an entity-type permissions form route.
 */
trait PermissionsFormRouteTrait {

  /**
   * Gets the permissions-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getPermissionsFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('permissions-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('permissions-form'));

      // Use the permissions form handler.
      $operation = 'permissions';

      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          'entity_type_id' => $entity_type_id,
          '_title' => "Edit {$entity_type->getSingularLabel()} permissions",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

}
