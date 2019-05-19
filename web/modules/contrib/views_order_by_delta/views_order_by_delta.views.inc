<?php

/**
 * @file
 * Provide a custom views sort data that isn't tied to any other module.
 */

use \Drupal\field\Entity\FieldConfig;
use \Drupal\Core\Database\Database;

/**
 * Implements hook_views_data().
 */
function views_order_by_delta_views_data() {
  $data = array();

  $entity_manager = \Drupal::entityManager();
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $all_bundle_info = \Drupal::service("entity_type.bundle.info")->getAllBundleInfo();

  // Iterate over all entity types but consider only the ones
  // that are used by views.
  foreach ($entity_manager->getDefinitions() as $entity_type => $entity_info) {
    if ($entity_info->hasHandlerClass('views_data')) {
      // Get all bundles and fields and verify if these are entity_references.
      foreach ($all_bundle_info[$entity_type] as $bundle => $label) {
        foreach ($entity_field_manager->getFieldDefinitions($entity_type, $bundle) as $field) {
          // We don't need base fields.
          if ($field instanceof FieldConfig) {
            if ($field->get('field_type') == 'entity_reference') {
              // Get the table name and verify if this table has a delta column.
              $definitions = $field->getFieldStorageDefinition();
              $storage = $entity_manager->getStorage($entity_type);
              $table_name = $storage->getTableMapping()->getDedicatedDataTableName($definitions);
              $schema = Database::getConnection()->schema();
              // This if is probably not needed since all references should have
              // a delta column.
              if ($schema->fieldExists($table_name, 'delta')) {
                $data[$entity_info->getBaseTable()][$table_name . '__views_order_by_delta'] = array(
                  'title' => t('Order by delta (using @field)', array('@field' => $field->get('field_name'))),
                  'help' => t('Custom sort.'),
                  'sort' => array(
                    'id' => 'order_by_delta',
                  ),
                );
              }
            }
          }
        }
      }
    }
  }
  return $data;
}
