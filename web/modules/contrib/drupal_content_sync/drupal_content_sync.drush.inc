<?php

/**
 * @file
 * Contains Drush commands for Drupal Content Sync.
 */

use Drupal\drupal_content_sync\Entity\Pool;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\ApiUnifyPoolExport;
use Drupal\drupal_content_sync\ApiUnifyFlowExport;

/**
 * Implements hook_drush_command().
 */
function drupal_content_sync_drush_command() {
  $items['drupal-content-sync-export'] = [
    'description' => dt('Export configuration to the Content Sync backend.'),
    'aliases' => ['dcse'],
  ];

  return $items;
}

/**
 * Export configuration to the Content Sync backend.
 */
function drush_drupal_content_sync_export() {
  drush_print('Started export of pools.');
  foreach (Pool::getAll() as $pool) {
    $exporter = new ApiUnifyPoolExport($pool);
    $steps    = $exporter->prepareBatch();
    foreach ($steps as $step) {
      $exporter->executeBatch($step);
    }
  }
  drush_print('Finished export of pools.');
  drush_print('Started export of flows.');
  foreach (Flow::getAll() as $flow) {
    $exporter = new ApiUnifyFlowExport($flow);
    $steps    = $exporter->prepareBatch();
    foreach ($steps as $step) {
      $exporter->executeBatch($step);
    }
  }
  drush_print('Finished export of flows.');
}
