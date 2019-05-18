<?php

/**
 * @file
 * Documentation for Record module APIs.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define custom (database) properties (columns) for a record bundle.
 *
 * @param FieldStorageDefinitionInterface $field_definition
 *   A field definition as passed to the schema method of a field item.
 *
 * @return array
 *   Array that conforms to the Drupal schema API.
 *
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Database%21database.api.php/group/schemaapi/8.5.x
 */
function hook_record_extended_schema(FieldStorageDefinitionInterface $field_definition) {
  $schema['columns'] = [];
  $field_name = $field_definition->get('field_name');

  // Note the field_name is the same as the bundle name.
  if ($field_name == 'contact') {
    $schema['columns']['name'] = record_property('int', t('Name'), 25);
    $schema['columns']['mobile'] = record_property('int', t('Mobile'), 10);
    $schema['columns']['age'] = record_property('int', t('Age'));
  }
  return $schema;
}

/**
 * @} End of "addtogroup hooks".
 */
