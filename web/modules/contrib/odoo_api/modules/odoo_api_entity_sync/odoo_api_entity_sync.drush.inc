<?php

/**
 * @file
 * Odoo api entity sync module Drush commands file.
 */

/**
 * Implements hook_drush_command().
 */
function odoo_api_entity_sync_drush_command() {
  $items['odoo-api_entity-sync-process-sync-queue'] = [
    'description' => 'Processes the sync queue.',
    'options' => [
      'cron-export' => [
        'description' => 'Controls fetching items exported on cron. 1 - get items which should be exported on cron. 0 - get all items excluded from cron export. Skip this option to sync all items.',
        'example-value' => 1,
      ],
      'limit' => [
        'description' => 'The number of items to process.',
        'example-value' => 100,
      ],
      'items-per-pass' => [
        'description' => 'The number of items to process per one batch operation.',
        'example-value' => 50,
      ],
    ],
    'examples' => [
      'drush odoo-api_entity-sync-process-sync-queue',
      'drush odoo-api_entity-sync-process-sync-queue --limit=100 --cron-export=0 --items-per-pass=50',
    ],
  ];

  return $items;
}

/**
 * Drush command callback for odoo-api_entity-sync-process-sync-queue.
 */
function drush_odoo_api_entity_sync_process_sync_queue() {
  $args = [
    drush_get_option('limit') ?: 100,
    drush_get_option('items-per-pass') ?: 50,
    drush_get_option('cron-export'),
  ];
  $operations = [
    ['drush_odoo_api_entity_sync_process_sync_queue_process', $args],
  ];

  batch_set(['operations' => $operations]);
  drush_backend_batch_process();
}

/**
 * Implements callback_batch_operation().
 *
 * Processes the sync queue.
 *
 * @param int $limit
 *   The number of items to process.
 * @param int $items_per_pass
 *   The number of items to process per one batch operation.
 * @param bool|null $cron_export
 *   Controls fetching items exported on cron.
 *   Possible values are:
 *
 *   - NULL: get all items (do not apply condition).
 *   - TRUE: get items which should be exported on cron.
 *   - FALSE: get all items excluded from cron export.
 * @param array|\ArrayAccess $context
 *   The batch context.
 */
function drush_odoo_api_entity_sync_process_sync_queue_process($limit, $items_per_pass, $cron_export, &$context) {
  if ($items_per_pass > $limit) {
    throw new InvalidArgumentException('The items per pass number can not be greater than limit.');
  }

  /** @var \Drupal\odoo_api_entity_sync\MappingManagerInterface $mapping */
  $mapping = \Drupal::service('odoo_api_entity_sync.mapping');
  /** @var \Drupal\odoo_api_entity_sync\SyncManagerInterface $sync */
  $sync = \Drupal::service('odoo_api_entity_sync.sync');

  if ($cron_export !== NULL) {
    $cron_export = (bool) $cron_export;
  }

  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['count_sync_queue'] = $mapping->countSyncQueue($cron_export);
    $context['sandbox']['max'] = $context['sandbox']['count_sync_queue'];

    if ($context['sandbox']['max'] > $limit) {
      $context['sandbox']['max'] = $limit;
    }
  }

  if ($queue = $mapping->getSyncQueue($items_per_pass, $cron_export)) {
    foreach ($queue as $entity_type => $odoo_models) {
      foreach ($odoo_models as $odoo_model => $export_types) {
        foreach ($export_types as $export_type => $entity_ids) {
          $sync->delayedSync($entity_type, $odoo_model, $export_type, $entity_ids);
        }
      }
    }

    $sync->syncAndFlush();
    // Progress = old number of items to sync - new number of items to sync.
    $context['sandbox']['progress'] = $context['sandbox']['count_sync_queue'] - $mapping->countSyncQueue($cron_export);
  }

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    $percentage = number_format($context['finished'] * 100, 2);
    drush_log('Synced ' . $context['sandbox']['progress'] . ' queue items of ' . $context['sandbox']['max'] . ' (' . $percentage . '%).');
  }

}
