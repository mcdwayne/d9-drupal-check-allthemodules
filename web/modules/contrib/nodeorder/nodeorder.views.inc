<?php

/**
 * @file
 * Views integration for nodeorder module.
 */

/**
 * Implements hook_views_data_alter().
 */
function nodeorder_views_data_alter(&$data) {
  // Taxonomy weight.
  $data['taxonomy_index']['weight'] = [
    'title' => t('Nodeorder'),
    'help' => t('The node weight in given term'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
      'label' => t('Nodeorder'),
    ],
    'sort' => [
      'id' => 'standard',
      'label' => t('Nodeorder'),
    ],
  ];
}
