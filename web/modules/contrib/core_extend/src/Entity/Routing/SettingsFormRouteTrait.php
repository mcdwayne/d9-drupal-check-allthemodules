<?php

namespace Drupal\core_extend\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a trait for declaring an entity-type settings form route.
 */
trait SettingsFormRouteTrait {

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType() && $entity_type->hasLinkTemplate('settings') && $route = new Route($entity_type->getLinkTemplate('settings'))) {
      $route
        ->setDefaults([
          '_form' => $entity_type->getFormClass('settings'),
          '_title' => "{$entity_type->getSingularLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

}
