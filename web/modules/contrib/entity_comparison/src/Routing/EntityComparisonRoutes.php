<?php

/**
 * @file
 * Contains \Drupal\entity_comparison\Routing\EntityComparisonRoutes.
 */

namespace Drupal\entity_comparison\Routing;

use Drupal\entity_comparison\Entity\EntityComparison;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class EntityComparisonRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {

    $routes = array();

    // Load all entity comparison configuration entity
    $entity_comparisons = EntityComparison::loadMultiple();

    // Go through all of them
    foreach ($entity_comparisons as $id => $entity_comparison) {
      $routes['entity_comparison.compare.' . $id] = new Route(
      // Path to attach this route to:
        '/compare/' . str_replace('_', '-', $id),
        // Route defaults:
        array(
          '_controller' => '\Drupal\entity_comparison\Controller\EntityComparisonController::compare',
          '_title' => $entity_comparison->label(),
        ),
        // Route requirements:
        array(
          '_permission'  => "use $id entity comparison"
        )
      );
    }

    return $routes;
  }

}