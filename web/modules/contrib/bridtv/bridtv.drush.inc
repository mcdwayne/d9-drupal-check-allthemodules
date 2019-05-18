<?php
/**
 * @file Contains Drush commands for Brid.TV integration.
 */

/**
 * Implements hook_drush_command().
 */
function bridtv_drush_command() {
  $items = [];
  $items['bridtv-sync'] = [
    'description' => 'Synchronizes the video data with the Brid.TV service.',
    'arguments' => [],
    'options' => [
      'limit' => 'The number of queue items to synchronize.',
    ],
    'drupal dependencies' => ['bridtv'],
    'examples' => [
      'drush bridtv-sync --limit=10' => 'Synchronizes 10 video items.',
    ],
  ];
  return $items;
}

/**
 * Synchronizes the video data with the Brid.TV service.
 */
function drush_bridtv_sync() {
  $options = [
    'limit' => drush_get_option('limit'),
  ];

  /** @var \Drupal\bridtv\BridSync $sync */
  $sync = \Drupal::service('bridtv.sync');
  $limit = isset($options['limit']) ? (int) $options['limit'] : -1;

  $sync->run($limit);
}
