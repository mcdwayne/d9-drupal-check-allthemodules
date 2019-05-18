<?php

/**
 * @file
 * Implements hook_views_data_alter().
 */

/**
 * Implements hook_views_data_alter().
 */
function file_management_views_data_alter(array &$data) {
  $data['file_managed']['file_management_operations'] = [
    'field' => [
      'title' => t('Operations links'),
      'help' => t('Provides links to perform entity operations.'),
      'id' => 'file_management_operations',
    ],
  ];

  return $data;
}
