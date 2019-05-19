<?php

/**
 * @file
 * Views hooks for weeklytime.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * @see datetime_field_views_data()
 */
function weeklytime_field_views_data(FieldStorageConfigInterface $field_storage) {
  $name = $field_storage->getName();
  $time_field = $name . '_time';
  $days_field = $name . '_days';
  $bundles_names = $field_storage->getBundles();

  $data = views_field_default_views_data($field_storage);
  foreach ($data as $table_name => $table_data) {
    // Change the existing time field to use our filter.
    $data[$table_name][$time_field]['filter']['id'] = 'weeklytime';
    $data[$table_name][$time_field]['filter']['title'] = t('Weekly Time filter');

    // Add a filter for the days.
    $data[$table_name][$days_field] = [
      'title' => t('Weekly Days filter'),
      'help' => t('Appears in: @bundles.', array('@bundles' => implode(', ', $bundles_names))),
      'group' => $data[$table_name][$time_field]['group'],
      'filter' => [
        'id' => 'weeklydays',
        'additional fields' => [
          'field_time_sun',
          'field_time_mon',
          'field_time_tue',
          'field_time_wed',
          'field_time_thu',
          'field_time_fri',
          'field_time_sat',
        ],
        'allow empty' => TRUE,
      ],
    ];
  }

  return $data;
}
