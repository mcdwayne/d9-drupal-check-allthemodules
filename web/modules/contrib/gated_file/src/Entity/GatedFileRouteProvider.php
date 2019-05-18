<?php

namespace Drupal\gated_file\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the gate file entities.
 */
class GatedFileRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/gated_file/form/{gated_file}'))
      ->addDefaults([
        '_controller' => '\Drupal\gated_file\Controller\GatedFileController::form',
      ])
      ->setRequirement('gated_file', '\d+')
      // @todo Create permissions to view the form.
      ->setRequirement('_permission', 'access content');
    $route_collection->add('entity.gated_file.canonical', $route);

    return $route_collection;
  }

}
