<?php
/**
 * @file
 *
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for datetime fields.
 * Adds a YYYYMM relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function pe_event_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'datetime') {
    $argument_type = 'yearmonth';
    foreach ($data as $table_name => $table_data) {
      $group = $data[$table_name][$field_storage->getName() . '_value']['group'];
      $data[$table_name][$field_storage->getName() . '_' . $argument_type . '_value'] = [
        'title' => $field_storage->getLabel() . ' (' . $argument_type . ')',
        'help' => t('Date in the form of YYYYMM.'),
        'argument' => [
          'field' => $field_storage->getName() . '_value',
          'id' => 'datetime_' . $argument_type,
        ],
        'group' => $group,
      ];
    }
  }
}
