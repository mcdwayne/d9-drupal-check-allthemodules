<?php

namespace Drupal\nodequeue_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * Destination for entity subqueues.
 *
 * @MigrateDestination(
 *   id = "entity:entity_subqueue"
 * )
 */
class EntityNodeSubqueue extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $id_key = $this->getKey('id');
    $ids[$id_key]['type'] = 'string';
    return $ids;
  }

}
