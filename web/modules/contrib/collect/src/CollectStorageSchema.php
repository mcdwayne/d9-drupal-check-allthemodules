<?php

/**
 * @file
 * Contains \Drupal\collect\CollectStorageSchema.
 */

namespace Drupal\collect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the collect container schema handler.
 */
class CollectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['collect']['indexes'] += array(
      'type' => array('type'),
      'origin_uri' => array(array('origin_uri', 255)),
      'schema_uri' => array(array('schema_uri', 255)),
    );

    return $schema;
  }

}
