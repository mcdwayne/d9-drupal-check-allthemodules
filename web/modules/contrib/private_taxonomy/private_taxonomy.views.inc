<?php

/**
 * @file
 * Integration of Private Taxonomy with Views.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data().
 */
function private_taxonomy_views_data() {
  $data['user_term'] = [
    'table' => [
      'group' => t('Private Taxonomy term'),
      'join' => [
        'taxonomy_term_field_data' => [
          'left_field' => 'tid',
          'field' => 'tid',
        ],
      ],
    ],
  ];
  $data['user_term']['uid'] = [
    'title' => t('Current user'),
    'help' => t('Filter the terms for the current user.'),
    'filter' => [
      'id' => 'user_current',
    ],
    'argument' => [
      'title' => t('User'),
      'help' => t('Filter the terms by user.'),
      'id' => 'user_uid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'uid',
    ],
  ];

  return $data;
}

/**
 * Implements hook_field_views_data().
 */
function private_taxonomy_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['filter']['id'] = 'private_taxonomy_index_tid';
        $allowed_values = $field_storage->getSetting('allowed_values');
        $data[$table_name][$field_name]['filter']['vocabulary'] = $allowed_values[0]['vocabulary'];
        $data[$table_name][$field_name]['filter']['users'] = $allowed_values[0]['users'];
      }
    }

    $field_name = $field_storage->getName();
    $parameters = ['@field_name' => $field_name];
    $data[$table_name][$field_name . '_target_id']['relationship'] = [
      'id' => 'standard',
      'base' => 'taxonomy_term_field_data',
      'base field' => 'tid',
      'label' => t('Term referenced from @field_name', $parameters),
      'group' => t('Taxonomy'),
      'title' => t('Referenced term'),
      'help' => t('A bridge to the term that is referenced via @field_name',
        $parameters),
    ];
  }
  return $data;
}
