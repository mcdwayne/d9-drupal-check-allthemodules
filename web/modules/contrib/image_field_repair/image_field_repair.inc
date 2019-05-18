<?php

/**
 * @file
 * Provides a service to repair the dimensions of image field values.
 */

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\image_field_repair\ImageSizer;

/**
 * Callback that processes a partial batch run to repair image fields.
 *
 * Note: by only passing in the sandbox, this function can serve as a
 * hook_update_N() hook. This module will contain a wrapper around this function
 * to serve in form batch processing when this code is used in a stand alone
 * module.
 *
 * @param array $sandbox
 *   Array keeping track of the progress and other information that needs to be
 *   passed between subsequent calls to this batch operation callback function.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup|\Drupal\Core\StringTranslation\TranslatableMarkup[]|null
 *   Batch information.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
 */
function image_field_repair_process_batch(&$sandbox) {
  $entityTypeManager = \Drupal::entityTypeManager();
  $database = \Drupal::database();

  if (!isset($sandbox['field_ids'])) {
    // First run: initialize fields to repair.
    $sandbox['batch_size'] = 100;
    $sandbox['field_ids'] = image_field_repair_get_image_field_ids($entityTypeManager);
    $sandbox['table_infos'] = image_field_repair_get_all_table_infos($sandbox['field_ids'], $entityTypeManager, $database);
    $sandbox['current_table_index'] = 0;
    $sandbox['current_range_start'] = 0;
    $sandbox['total'] = array_sum(array_column($sandbox['table_infos'], 'record_count'));
    $sandbox['current_table_processed'] = 0;
    $sandbox['current_table_repaired'] = 0;
    $sandbox['processed'] = 0;
    $sandbox['repaired'] = 0;
    $sandbox['#finished'] = $sandbox['total'] === 0 ? 1 : 0;

    return $sandbox['#finished'] === 1 ? t('Not found any image records') : NULL;
  }

  image_field_repair_process_table_range($sandbox, $database);
  return image_field_repair_update_result($sandbox);
}

/**
 * Update result.
 *
 * @param array $sandbox
 *   Array keeping track of the progress and other batch information.
 *
 * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup|null
 *   Batch information.
 */
function image_field_repair_update_result(&$sandbox) {
  $result = NULL;
  $processed = $sandbox['current_processed'];
  $repaired = $sandbox['current_repaired'];
  $batch_size = $sandbox['batch_size'];

  // Update $sandbox.
  $sandbox['message'] = t('Processed records %from to %to of table %table for image field %field. %repaired of those had to be repaired.', [
    '%from' => $sandbox['processed'],
    '%to' => $sandbox['processed'] + $processed,
    '%table' => $sandbox['table_infos'][$sandbox['current_table_index']]['table_name'],
    '%field' => $sandbox['table_infos'][$sandbox['current_table_index']]['field_id'],
    '%repaired' => $repaired,
  ]);
  $sandbox['current_table_processed'] += $processed;
  $sandbox['current_table_repaired'] += $repaired;
  $sandbox['processed'] += $processed;
  $sandbox['repaired'] += $repaired;
  $sandbox['current_range_start'] += $processed;
  // If someone deleted an image during the update, current_table_processed may
  // never reach the stored record count, so we also check if the query returned
  // less than the requested number of records.
  if ($sandbox['current_table_processed'] >= $sandbox['table_infos'][$sandbox['current_table_index']]['record_count'] || $processed < $batch_size) {
    // End of this table: return statistics for this table and proceed to the
    // next table:
    if ($sandbox['current_table_repaired'] > 0) {
      $result = t('Repaired %repaired out of %total records in table "%table" for image field "%field".', [
        '%total' => $sandbox['current_table_processed'],
        '%table' => $sandbox['table_infos'][$sandbox['current_table_index']]['table_name'],
        '%field' => $sandbox['table_infos'][$sandbox['current_table_index']]['field_id'],
        '%repaired' => $sandbox['current_table_repaired'],
      ]);
    }

    $sandbox['current_table_index']++;
    $sandbox['current_range_start'] = 0;
    $sandbox['current_table_processed'] = 0;
    $sandbox['current_table_repaired'] = 0;
  }

  $sandbox['#finished'] = $sandbox['processed'] / $sandbox['total'];
  // If someone deleted an image during the update, #finished will never
  // become 1, we so also indicate that we have finished when we have
  // processed all tables.
  if ($sandbox['current_table_index'] >= count($sandbox['table_infos'])) {
    $sandbox['#finished'] = 1;
  }

  // When we are finished we also return a summary message.
  if ($sandbox['#finished'] === 1) {
    $result = [
      $result,
      t('Total: Repaired %repaired out of %total checked records in %number_of_fields image fields.', [
        '%repaired' => $sandbox['repaired'],
        '%total' => $sandbox['processed'],
        '%number_of_fields' => count($sandbox['field_ids']),
      ]),
    ];
  }

  return $result;
}

/**
 * Checks and repairs a range of records in a given image field table.
 *
 * @param array &$sandbox
 *   Batch information.
 * @param \Drupal\Core\Database\Connection $database
 *   The current database.
 */
function image_field_repair_process_table_range(&$sandbox, Connection $database) {
  $processed = 0;
  $repaired = 0;

  $imageSizer = new ImageSizer();
  $table_info = $sandbox['table_infos'][$sandbox['current_table_index']];
  $table_name = $table_info['table_name'];
  $foreign_table_name = $table_info['join_field']['foreign_table_name'];
  $foreign_field = $table_info['join_field']['foreign_field_name'];
  $table_field = $table_info['join_field']['join_field_name'];
  // Query all columns of the image field (as we use almost all of them).
  $query = $database->select($table_name)
    ->fields($table_name);
  // Introduce a strict order by to ensure that the ranges do not miss any
  // record due to random/unknown ordering.
  foreach ($table_info['primary_key_fields'] as $primary_key_field) {
    $query->orderBy("$table_name.$primary_key_field");
  }
  // Join with the file_managed table as we need the image uri to get the actual
  // dimensions.
  $join_condition = "$foreign_table_name.$foreign_field = $table_name.$table_field";
  $query->join($foreign_table_name, NULL, $join_condition);
  $query->fields($foreign_table_name, ['uri']);
  // Add a range to stay well within the limits of batch processing.
  $query->range($sandbox['current_range_start'], $sandbox['batch_size']);

  // Get and process the records.
  $results = $query->execute();
  while ($row = $results->fetchAssoc()) {
    // Get actual image dimensions and compare with stored image dimensions.
    // Do NOT use the image toolkit: we don't need it and we don't want to
    // depend on it or its current list of supported image types here.
    $image_size = $imageSizer->getDimensions($row['uri']);
    if (is_array($image_size)) {
      if ((int) $row[$table_info['width_field']] !== $image_size[0] || (int) $row[$table_info['height_field']] !== $image_size[1]) {
        // Dimensions are not equal: repair current record (identified by its
        // primary key values).
        $update_query = $database->update($table_name)
          ->fields([
            $table_info['width_field'] => $image_size[0],
            $table_info['height_field'] => $image_size[1],
          ]);
        foreach ($table_info['primary_key_fields'] as $primary_key_field) {
          $update_query->condition($primary_key_field, $row[$primary_key_field]);
        }
        $repaired += $update_query->execute();
      }
    }
    $processed++;
  }
  $sandbox['current_processed'] = $processed;
  $sandbox['current_repaired'] = $repaired;
}

/**
 * Returns a list of image fields ids.
 *
 * We return all image fields, not only those that use a multi-upload widget
 * (required to be touched by issue [#2644468]) as that widget may have been
 * disabled due to this very issue.
 *
 * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
 *   The entity type manager.
 *
 * @return string[]
 *   A list of image fields ids.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 */
function image_field_repair_get_image_field_ids(EntityTypeManagerInterface $entityTypeManager) {
  /** @var \Drupal\field\FieldStorageConfigStorage $storage */
  $storage = $entityTypeManager->getStorage('field_storage_config');
  $fieldIds = $storage->getQuery()
    ->condition('module', 'image')
    ->execute();
  return array_values($fieldIds);
}

/**
 * Returns info about the data and revision tables for all given image fields.
 *
 * @param string[] $field_ids
 *   Array of ids of image fields.
 * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
 *   The entity type manager.
 * @param \Drupal\Core\Database\Connection $database
 *   The current database.
 *
 * @return array[]
 *   Table info about data and revision tables for all given image fields.
 *
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
 *
 * @todo: to support other storage types we could check if the storage uses SQL
 *   tables ($entityTypeManager->getStorage($target_entity_type_id) returns a
 *   SqlContentEntityStorage) and if so, just use the database (faster), and if
 *   not so, use entity/field queries (slower and hooks are called which we may
 *   not want during an update).
 */
function image_field_repair_get_all_table_infos(array $field_ids, EntityTypeManagerInterface $entityTypeManager, Connection $database) {
  $tables = [];
  /** @var \Drupal\field\FieldStorageConfigStorage $storage */
  $storage = $entityTypeManager->getStorage('field_storage_config');
  /** @var \Drupal\field\FieldStorageConfigInterface[] $fields */
  $fields = $storage->loadMultiple(array_values($field_ids));
  foreach ($fields as $field) {
    $target_entity_type_id = $field->getTargetEntityTypeId();
    /** @var \Drupal\Core\Entity\EntityStorageInterface $target_storage_definition */
    $target_storage_definition = $entityTypeManager->getStorage($target_entity_type_id);
    if ($target_storage_definition instanceof SqlContentEntityStorage) {
      $table_mapping = $target_storage_definition->getTableMapping();
      $tables[] = image_field_repair_get_field_table($field, $table_mapping, $database);
      $tables[] = image_field_repair_get_field_table_revision($field, $table_mapping, $target_entity_type_id, $database);
    }
  }

  // Remove empty entries (for fields that do not use Sql storage or for non
  // revisioned fields).
  return array_values(array_filter($tables));
}

/**
 * Returns the data tables info.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field
 *   An image field.
 * @param \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping
 *   A table mapping object for the entity's tables.
 * @param \Drupal\Core\Database\Connection $database
 *   The current database.
 *
 * @return array
 *   Info about the data tables for the given image field.
 *
 * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
 */
function image_field_repair_get_field_table(FieldStorageConfigInterface $field, DefaultTableMapping $table_mapping, Connection $database) {
  $primary_keys = [
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ];
  $table_name = $table_mapping->getDedicatedDataTableName($field);
  return image_field_repair_get_table_info($field, $table_mapping, $table_name, $primary_keys, $database);
}

/**
 * Returns revision table info.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field
 *   An image field.
 * @param \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping
 *   A table mapping object for the entity's tables.
 * @param string $target_entity_type_id
 *   Target entity type id.
 * @param \Drupal\Core\Database\Connection $database
 *   The current database.
 *
 * @return array
 *   Info about the revision tables for the given image field.
 *
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
 */
function image_field_repair_get_field_table_revision(FieldStorageConfigInterface $field, DefaultTableMapping $table_mapping, $target_entity_type_id, Connection $database) {
  $primary_keys = [
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'langcode',
  ];
  $entityTypeManager = \Drupal::entityTypeManager();
  $target_entity_type = $entityTypeManager->getDefinition($target_entity_type_id);
  // Only if both the entity type the field is attached to, and the field are
  // revisionable, a revision table will exist.
  if ($target_entity_type && $target_entity_type->isRevisionable() && $field->isRevisionable()) {
    $table_name = $table_mapping->getDedicatedRevisionTableName($field);
    return image_field_repair_get_table_info($field, $table_mapping, $table_name, $primary_keys, $database);
  }
  return [];
}

/**
 * Returns the table info for 1 field table.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field
 *   The field to get the table name for.
 * @param \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping
 *   A table mapping object for the entity's tables.
 * @param string $table_name
 *   The table name.
 * @param array $primary_keys
 *   The primary keys.
 * @param \Drupal\Core\Database\Connection $database
 *   The current database.
 *
 * @return array
 *   An array with information about a field table that is needed later on in
 *   the process. An empty array is returned if no table could be found, either
 *   because the field does not use sql storage or because the field is not
 *   revisioned.
 *
 * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
 */
function image_field_repair_get_table_info(FieldStorageConfigInterface $field, DefaultTableMapping $table_mapping, $table_name, $primary_keys, Connection $database) {
  // Get the record count of this table.
  $record_count = $database->select($table_name)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Only return this table if it is not empty.
  if ($record_count > 0) {
    $foreign_key = ImageItem::schema($field)['foreign keys']['target_id'];
    return [
      'field_id' => $field->id(),
      'table_name' => $table_name,
      'record_count' => $record_count,
      'primary_key_fields' => $primary_keys,
      'join_field' => [
        'foreign_table_name' => $foreign_key['table'],
        'foreign_field_name' => reset($foreign_key['columns']),
        'join_field_name' => $table_mapping->getFieldColumnName($field, key($foreign_key['columns'])),
      ],
      'width_field' => $table_mapping->getFieldColumnName($field, 'width'),
      'height_field' => $table_mapping->getFieldColumnName($field, 'height'),
    ];
  }

  return [];
}
