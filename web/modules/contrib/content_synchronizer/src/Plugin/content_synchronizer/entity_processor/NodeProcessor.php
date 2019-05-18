<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\entity_processor;

use Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @EntityProcessor(
 *   id = "content_synchronizer_node_processor",
 *   entityType = "node"
 * )
 */
class NodeProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getDataToExport(EntityInterface $entityToExport) {
    // Init data to export:
    $data = parent::getDataToExport($entityToExport);

    // Add bundle :
    $data['type'] = $entityToExport->bundle();

    return $data;
  }

}
