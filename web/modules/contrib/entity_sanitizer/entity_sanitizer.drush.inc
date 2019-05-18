<?php

/**
 * @file Custom drush command to sanitize Entity data.
 */

use \Drupal\Component\Utility\Random;

/**
 * Implementation of hook_drush_help().
 */
function entity_sanitizer_drush_help($section) {
  switch ($section) {
    case 'drush:entity-sanitize':
      return dt('Run sanitizing function for all Entity data');
  }
}


/**
 * Implements hook_drush_command().
 */
function entity_sanitizer_drush_command() {
  $items = array();

  $items['entity-sanitize'] = array(
    'description' => "Run sanitization operations for this website.",
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'drush dependencies' => array('entity_sanitizer'),
    'options' => array(),
    'aliases' => array('es'),
  );

  return $items;
}

/**
 * Callback to sanitize this website.
 *
 * @see sql_drush_sql_sync_sanitize().
 * @see drush_sql_sanitize().
 */
function drush_entity_sanitizer_entity_sanitize() {
  drush_bootstrap_max(DRUSH_BOOTSTRAP_DRUPAL_DATABASE);

  /** @var \Drupal\entity_sanitizer\Sanitizer $sanitizer */
  $sanitizer = \Drupal::service('entity_sanitizer.sanitizer');

  // We retrieve the storage definitions for all fields stored in a database.
  // This automatically excludes any computed fields.
  $entities = $sanitizer->getAllEntityFieldDefinitions();

  // Our array with sanitizing operations.
  $sanitize_orders = [];

  echo "Sanitizing the following entity bundles: \n";

  // TODO DEBUG: LIMIT TO A SINGLE ENTITY/BUNDLE FOR EASE!
//  $entities = ['node' => $entities['node']];
//  $entities['node'] = ['page' => $entities['node']['page']];

  foreach($entities as $entity_id => $bundles) {
    // TODO: This is a good place for the entity to be skippable.

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entity_storage */
    $entity_storage = \Drupal::service('entity_type.manager')->getStorage($entity_id);

    // We only know how to handle fields stored in SQL.
    if (!$entity_storage instanceof \Drupal\Core\Entity\Sql\SqlEntityStorageInterface) {
      echo "Skipping {$entity_id} sanitizing because it's not stored in SQL.\n";
      continue;
    }

    echo "\n{$entity_id} \n";

    foreach ($bundles as $bundle_id => $fields) {
      echo "- {$bundle_id} \n";

      // TODO: This is a good place for this bundle to be skippable.

      // Use the storage definitions to create a sanitizing order.
      foreach ($fields as $field_name => $definition) {
        /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $definition */

        // TODO: This is a good place for this field to be skippable.


        // Fields with custom storage have no table info so we skip them.
        if ($definition->hasCustomStorage()) {
          echo "Skipping field {$field_name} as it has custom storage.\n";
          continue;
        }

        // TODO: Base fields don't have their own table so they need special attention.
        if ($definition->isBaseField()) {
          echo "Skipped base field {$field_name} ({$definition->getType()}).\n";
          continue;
        }

        // The type of field determines how we create the sanitized value.
        $field_type = $definition->getType();

        // Every field has a table for the non-revisioned version of the field.
        $revision_table_types = [FALSE];

        // Some also have a field revision table so they require more work.
        if ($entity_storage->getEntityType()->isRevisionable() && $definition->isRevisionable()) {
          $revision_table_types[] = TRUE;
        }

        foreach ($revision_table_types as $revision_table) {
          $table_name = $sanitizer->generateFieldTableName($definition, $revision_table);

          // Create an order to sanitize this table.
          if (!isset($sanitize_orders[$table_name])) {
            $sanitize_orders[$table_name] = [
              'field_name' => $field_name,
              'field_type' => $field_type,
              'is_revision_table' => $revision_table,
              'definition' => $definition,
              'bundles' => []
            ];
          }

          // Add this bundle as one to be sanitized.
          $sanitize_orders[$table_name]['bundles'][] = $bundle_id;

          // TODO: Remove this debug output.
          echo "-- {$table_name} ({$field_type})\n";
        }
      }
    }
  }

  echo PHP_EOL;

  // We generate the queries now so we can report any missing fields before
  // asking the user to continue.
  $queries = [];
  $unsupported_types = [];

  foreach ($sanitize_orders as $table_name => $order) {
    try {
      $queries[] = $sanitizer->generateSanitizeQuery($table_name, $order);
    }
    catch (\Drupal\entity_sanitizer\UnsupportedFieldTypeException $exception) {
      $field_type = $exception->getFieldType();

      if (empty($unsupported_types[$field_type])) {
        $unsupported_types[$field_type] = [];
      }

      $unsupported_types[$field_type][] = $exception->getFieldName();
    }
  }

  if (!empty($unsupported_types)) {
    echo "------------------------------------------------" . PHP_EOL;
    echo "The following field types were not supported and fields of those types will not be sanitized!" . PHP_EOL;
    foreach ($unsupported_types as $type => $fields) {
      $output_fields = join(", ", $fields);
      echo PHP_EOL . "{$type} ({$output_fields})" . PHP_EOL;
    }
    echo "------------------------------------------------" . PHP_EOL . PHP_EOL;
  }

  if (!drush_confirm(dt('Do you really want to sanitize all these entities?'))) {
    return drush_user_abort();
  }

  foreach ($queries as $query) {
    // If we don't need a query for this order (e.g. entity reference) then we
    // receive NULL instead of a query to execute.
    if (!empty($query)) {
      $query->execute();
    }
  }

  foreach($entities as $entity_id => $fields) {
    foreach($fields as $field_name => $definition) {
      // TODO: Create a query for each field individually to update the value based on the field type

      // TODO: Add a WHERE condition to the UPDATE query to exclude whitelisted bundle/field combinations.

    }
  }

  echo PHP_EOL;

  // Clear all the caches so that no stale (unsanitized) content remains.
  drush_drupal_cache_clear_all();

}

