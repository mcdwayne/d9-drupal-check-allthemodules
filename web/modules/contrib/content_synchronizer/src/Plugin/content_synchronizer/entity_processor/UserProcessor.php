<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\entity_processor;

use Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @EntityProcessor(
 *   id = "content_synchronizer_user_processor",
 *   entityType = "user"
 * )
 */
class UserProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getDataToExport(EntityInterface $entityToExport) {
    return FALSE;
  }

}
