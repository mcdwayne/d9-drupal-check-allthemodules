<?php

/**
 * @file
 * Post update functions for Odoo API - Entity Sync Messages.
 */

use Drupal\odoo_api_entity_sync\MappingManagerInterface;

/**
 * Cleans up error messages.
 */
function odoo_api_entity_sync_messages_post_update_clean_up_error_messages(&$sandbox) {
  $fields = ['entity_type', 'odoo_model', 'export_type', 'entity_id', 'status'];
  $query = \Drupal::database()
    ->select('odoo_api_entity_sync')
    ->fields('odoo_api_entity_sync', $fields);

  $query->join('odoo_api_entity_sync_messages', 'messages', 'messages.entity_type=odoo_api_entity_sync.entity_type AND messages.odoo_model=odoo_api_entity_sync.odoo_model AND messages.export_type=odoo_api_entity_sync.export_type AND messages.entity_id=odoo_api_entity_sync.entity_id');

  $query->condition('odoo_api_entity_sync.status', [MappingManagerInterface::STATUS_SYNCED, MappingManagerInterface::STATUS_SYNC_EXCLUDED], 'IN');

  if (!isset($sandbox['progress'])) {
    $count_query = clone $query;
    $sandbox['progress'] = 0;
    $sandbox['max'] = $count_query->countQuery()->execute()->fetchField();
  }

  $items_per_pass = 200;
  $result = $query->range(0, $items_per_pass)->execute()->fetchAll();
  $delete = [];

  foreach ($result as $record) {
    $delete[$record->entity_type][$record->odoo_model][$record->export_type][] = $record->entity_id;
  }

  foreach ($delete as $entity_type => $odoo_models) {
    foreach ($odoo_models as $odoo_model => $export_types) {
      foreach ($export_types as $export_type => $entity_ids) {
        \Drupal::database()
          ->delete('odoo_api_entity_sync_messages')
          ->condition('entity_type', $entity_type, '=')
          ->condition('entity_id', $entity_ids, 'IN')
          ->condition('odoo_model', $odoo_model, '=')
          ->condition('export_type', $export_type, '=')
          ->execute();
        $sandbox['progress'] += count($entity_ids);
      }
    }
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
  drush_log('Fixed ' . $sandbox['progress'] . ' of ' . $sandbox['max'] . ' (' . number_format($sandbox['#finished'] * 100, 2) . '%).');

  if ($sandbox['#finished'] >= 1) {
    return t('Error messages has been cleaned up.');
  }
}
