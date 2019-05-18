<?php

namespace Drupal\external_entities\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a generic controller to list entities.
 */
class ExternalEntityListController extends ControllerBase {

  /**
   * Provides the listing page for any entity type.
   *
   * @param string $entity_type
   *   The entity type to render.
   * @param string $bundle
   *   The bundle of the entity type to render.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($entity_type, $bundle) {
    return $this
      ->entityTypeManager()
      ->getListBuilder($entity_type)
      ->setBundle($bundle)
      ->render();
  }

}
