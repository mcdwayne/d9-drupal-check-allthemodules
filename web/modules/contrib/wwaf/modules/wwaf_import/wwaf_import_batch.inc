<?php

/**
 * @file
 * Contains migration batch import functionality.
 */

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

/**
 * WWAF import process.
 *
 * @param string $id
 *   Import id.
 * @param string $type
 *   Import, update, rollback.
 * @param mixed $context
 *   The batch context.
 *
 * @throws \Drupal\migrate\MigrateException
 */
function wwaf_import_batch($id, $type, &$context) {
  $migrationConfig = \Drupal::service('plugin.manager.migration');
  $plugins = $migrationConfig->createInstances([$id]);
  switch ($type) {
    case 'import':
      $migrate = new MigrateExecutable($plugins[$id], new MigrateMessage());
      $migrate->import();
      break;

    case 'update':
      /** @var \Drupal\migrate\Plugin\Migration $plugin */
      $plugin = $plugins[$id];
      $plugin->getIdMap()->prepareUpdate();
      $migrate = new MigrateExecutable($plugins[$id], new MigrateMessage());
      $migrate->import();
      break;

    case 'rollback':
      $migrate = new MigrateExecutable($plugins[$id], new MigrateMessage());
      $migrate->rollback();

      // Removed queue if imported nodes were deleted.
      \Drupal::queue('mh_pos_loc_google_place_id')->deleteQueue();
      \Drupal::queue('mh_pos_loc_queue_place_data_update')->deleteQueue();
      break;

    default:
  }
}
