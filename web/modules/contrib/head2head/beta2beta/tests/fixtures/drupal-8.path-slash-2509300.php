<?php

/**
 * @file
 * Contains additions to the database provided by beta 11 bare database.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set breadcrumb to *not* display on front or user/1.
$block_config = [
  'uuid' => 'ded1a276-9b6d-4aad-af0c-12195f1108a9',
  'langcode' => 'en',
  'status' => TRUE,
  'dependencies' => [
    'module' => [
      'system',
    ],
    'theme' => [
      'bartik',
    ],
  ],
  'id' => 'bartik_breadcrumbs',
  'theme' => 'bartik',
  'region' => 'breadcrumb',
  'weight' => 0,
  'provider' => NULL,
  'plugin' => 'system_breadcrumb_block',
  'settings' => [
    'id' => 'system_breadcrumb_block',
    'label' => 'Breadcrumbs',
    'provider' => 'system',
    'label_display' => '0',
    'cache' => [
      'max_age' => -1,
    ],
  ],
  'visibility' => [
    'request_path' => [
      'id' => 'request_path',
      'pages' => "user/1\r\n<front>",
      'negate' => TRUE,
      'context_mapping' => [],
    ],
  ],
];
$connection->update('config')
  ->fields([
    'data' => serialize($block_config),
  ])
  ->condition('name', 'block.block.bartik_breadcrumbs')
  ->condition('collection', '')
  ->execute();

// Populate old aliases.
$connection->insert('url_alias')
  ->fields(['source', 'alias', 'langcode'])
  ->values([
    'source' => 'source1',
    'alias' => 'destination1',
    'langcode' => 'FR',
  ])
  ->values([
    'source' => 'source2',
    'alias' => 'destination2',
    'langcode' => 'FR',
  ])
  ->execute();

// Custom site.settings.
$data = [
  'uuid' => '020698b8-4200-41f0-9247-71421303d008',
  'name' => 'Site-Install',
  'mail' => 'admin@example.com',
  'slogan' => '',
  'page' => [
    403 => 'custom-403-page',
    404 => 'custom-404-page',
    'front' => 'custom-front-page',
  ],
  'admin_compact_mode' => FALSE,
  'weight_select_max' => 100,
  'langcode' => 'en',
  'default_langcode' => 'en',
];
Database::getConnection()
  ->update('config')
  ->fields([
    'data' => serialize($data),
  ])
  ->condition('name', 'system.site')
  ->execute();
