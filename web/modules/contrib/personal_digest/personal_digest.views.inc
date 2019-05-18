<?php

/**
 * Implements hook_views_data_alter().
 */
function personal_digest_views_data_alter(array &$tables) {
  foreach ($tables as &$table) {
    foreach ($table as $fieldname => &$handlers) {
      if (@$handlers['argument']['id'] === 'date_fulldate') {
        $table[$fieldname.'_since'] = [
          'title' => t('Since @fieldname', ['@fieldname' => $handlers['title']]),
          'help' => t('Indicates this view is suitable for personal digest emails.'),
          //'description' => t('Indicates this view is suitable for personal digest emails.'),
          'argument' => [
            'id' => 'date_fulldate_since',
            'field' => $handlers['argument']['field']
          ]
        ];
      }
    }
  }
}

