<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the content_entity_builder schema handler.
 */
class ContentEntityBuilderStorageSchema extends SqlContentEntityStorageSchema {
  
  /**
   * {@inheritdoc}
   */
   
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

	$content_type = \Drupal::entityManager()->getStorage('content_type')->load($table_name);
    $base_field = $content_type->getBaseField($field_name);

	if (isset($base_field) && !empty($base_field->hasIndex())) {
      $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
	}
    return $schema;
  }


}
