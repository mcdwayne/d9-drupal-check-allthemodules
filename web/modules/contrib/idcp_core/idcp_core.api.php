<?php

/**
 * Prepare demo data info files.
 *
 * @return array
 */
function hook_demo_data_info() {
  return [
    [
      'id' => 'migration_id',
      'source' => [
        'path' => drupal_get_path('module', 'module_name') . '/tests/data/demo.csv',
      ],
    ],
  ];
}

