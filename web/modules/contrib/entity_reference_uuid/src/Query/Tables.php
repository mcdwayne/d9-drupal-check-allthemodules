<?php

namespace Drupal\entity_reference_uuid\Query;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\Query\Sql\Tables as BaseTables;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Adds tables and fields to the SQL entity query.
 */
class Tables extends BaseTables {

  /**
   * {@inheritdoc}
   */
  protected function addNextBaseTable(EntityType $entity_type, $table, $sql_column, FieldStorageDefinitionInterface $field_storage = NULL) {
    if ((!$field_storage || $field_storage->getType() !== 'entity_reference_uuid') || ($entity_type->getKey('id') === FALSE)) {
      return parent::addNextBaseTable($entity_type, $table, $sql_column, $field_storage);
    }

    $join_condition = "%alias.uuid = $table.$sql_column";
    return $this->sqlQuery->leftJoin($entity_type->getBaseTable(), NULL, $join_condition);
  }

}
