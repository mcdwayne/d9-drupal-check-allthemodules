<?php

namespace Drupal\httpbl;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the host schema handler.
 */
class HostStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['httpbl_host']['primary key'] += array(
      $entity_type->getKey('id'),
      $entity_type->getKey('host_ip'),
    );

    return $schema;
  }

}
