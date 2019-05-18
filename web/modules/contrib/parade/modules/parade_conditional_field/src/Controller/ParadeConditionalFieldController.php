<?php

namespace Drupal\parade_conditional_field\Controller;

use Drupal\Core\Entity\Controller\EntityListController;

/**
 * Defines a controller to list parade conditional field instances.
 */
class ParadeConditionalFieldController extends EntityListController {

  /**
   * Shows the 'Parade conditional fields' page.
   *
   * @param string $entityTypeId
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($entityTypeId = NULL, $bundle = NULL) {
    return $this->entityTypeManager()->getListBuilder('parade_conditional_field')->render($entityTypeId, $bundle);
  }

}
