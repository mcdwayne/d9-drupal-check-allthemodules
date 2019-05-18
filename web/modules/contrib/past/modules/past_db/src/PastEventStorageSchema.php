<?php

namespace Drupal\past_db;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the past_db schema handler.
 */
class PastEventStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['past_event']['indexes']['severity'] = ['severity'];
    $schema['past_event']['indexes']['timestamp_severity'] = ['timestamp', 'severity'];
    $schema['past_event']['indexes']['module'] = ['module'];
    $schema['past_event']['indexes']['machine_name'] = ['machine_name'];
    $schema['past_event']['indexes']['session_id'] = ['session_id'];
    $schema['past_event']['indexes']['type'] = ['type'];

    return $schema;
  }

}
