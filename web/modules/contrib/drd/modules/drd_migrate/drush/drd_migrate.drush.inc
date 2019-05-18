<?php

/**
 * @file
 * Drush command file for Drush 8.
 */

/**
 * Implements hook_drush_command().
 */
function drd_migrate_drush_command() {
  $items = [
    'drd-migrate-from-d7' => [
      'arguments' => [
        'inventory' => ['description' => t('Filename containing the json with you DRD 7 inventory')],
      ],
    ],
  ];

  return $items;
}

/**
 * Callback to execute drd-migrate-from-d7 Drush command.
 *
 * @param string $inventory
 *   Filename which holds the inventory for migration.
 */
function drush_drd_migrate_from_d7($inventory) {
  \Drupal::service('drd_migrate.import')->execute($inventory);
}
