<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalStorageSchema.
 */

namespace Drupal\temporal;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the node schema handler.
 */
class TemporalStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['temporal']['indexes'] += array(
      'temporal__entity_id' => array('entity_id'),
      'temporal__type' => array('type'),
      'temporal__created' => array('created'),
      'temporal__changed' => array('changed'),
      'temporal__future' => array('future'),
    );

    return $schema;
  }
}
