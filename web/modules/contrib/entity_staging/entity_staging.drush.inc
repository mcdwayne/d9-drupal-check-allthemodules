<?php

/**
 * @file
 * Define all drush command for content staging.
 */

/**
 * Implements hook_drush_command().
 */
function entity_staging_drush_command() {
  return [
    'export-content' => [
      'description' => 'Export all contents.',
      'aliases' => ['ex'],
      'arguments' => [],
    ],
    'update-migration-config' => [
      'description' => 'Update migration config according the exported entities.',
      'aliases' => ['umc'],
      'arguments' => [],
    ],
  ];
}

/**
 * Callback function for 'export-content' drush command.
 */
function drush_entity_staging_export_content() {
  \Drupal::service('entity_staging.export')->export();
  _drush_log_drupal_messages();
}

function drush_entity_staging_update_migration_config() {
  \Drupal::service('entity_staging.import')->createMigrations();
  _drush_log_drupal_messages();
}
