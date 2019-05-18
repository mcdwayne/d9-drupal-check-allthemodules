<?php

/**
 * @file
 * Entity update module (entity_update).
 *
 * This file provide drush commands tasks.
 */

use Drupal\entity_update\EntityUpdate;
use Drupal\entity_update\EntityCheck;
use Drupal\entity_update\EntityUpdatePrint;

/**
 * Implements hook_drush_command().
 */
function entity_update_drush_command() {
  $items = [];

  // Entity update operations.
  $items['entity-update'] = [
    'description' => 'Update Entity type Schema',
    'aliases' => ['upe'],
    'arguments' => [
      'type' => dt('The entity type ID to update'),
    ],
    'options' => [
      'show' => dt('Show entities to update'),
      'basic' => dt('Update all entities as basic way'),
      'force' => dt('Try force update'),
      'all' => dt('Update all Entities'),
      'nobackup' => dt('Disable automatic full database backup (Not recommended)'),
      'clean' => dt('Cleanup entity backup database'),
      'bkpdel' => dt('Copy entities to update in to backup database and delete entities'),
      'rescue' => dt('Create entities from entity backup database'),
    ],
    'examples' => [
      'drush upe --show' => 'Show entities to update.',
      'drush upe --basic' => 'Update entities. Run this command if all entities to update are empty. Else Exception.',
      'drush upe --all' => 'Update entities with data. Run this command if any entity (to update) has data.',
      'drush upe --basic --nobackup' => 'Update all without automatic database backup.',
      'drush upe --basic --force --nobackup' => 'Try to update using basic method even having data.',
      'drush upe --all --nobackup' => 'Update all without automatic database backup (Not recommended).',
      'drush upe --clean' => 'Cleanup entity backup database.',
      'drush upe --bkpdel' => 'Copy entities to update in to backup database and delete entities.',
      'drush upe --rescue' => 'If entity recreation failed, You can you this option to create entities from entity backup database.',
      'drush upe ENTITY_TYPE_ID --nobackup' => 'Update entity type ENTITY_TYPE_ID.',
    ],
  ];

  // Check an entity type.
  $items['entity-check'] = [
    'description' => 'Check an Entity',
    'aliases' => ['upec'],
    'arguments' => [
      'type' => dt('The entity type ID'),
    ],
    'options' => [
      'types' => dt('Show entity types list'),
      'list' => dt('Show entities list'),
      'length' => dt('Number of entities to show'),
      'start' => dt('Start from'),
    ],
    'examples' => [
      'drush upec node' => 'Show The entity summery.',
      'drush upec --types' => 'Show all entity types list.',
      'drush upec block --types ' => 'Show all entity types list contains "block"',
      'drush upec node --list' => 'Show 10 entities.',
      'drush upec node --list --length=0' => 'Show all entities.',
      'drush upec node --list --start=2 --length=3' => 'Show 3 entities from 2.',
    ],
  ];
  return $items;
}

/**
 * Call back function of entity-update.
 */
function drush_entity_update($type = NULL) {

  // Show entities to update.
  if (drush_get_option('show')) {
    EntityCheck::showEntityStatusCli();
    return;
  }

  // Clean entity backup database.
  if (drush_get_option('clean')) {
    EntityUpdate::cleanupEntityBackup();
    drush_print("Entity backup data removed.");
    return;
  }

  // Restore all entities from database.
  if (drush_get_option('rescue')) {
    if (drush_confirm('Are you sure you want create entities from backup database ? ')) {
      $res = EntityUpdate::entityUpdateDataRestore();
      drush_log('End of entities recreate process', $res ? 'ok' : 'error');
    }
    return;
  }

  // Check mandatory options.
  $options = ['basic', 'all', 'bkpdel'];
  if (!$type && !_drush_entity_update_checkoptions($options)) {
    drush_log('No option specified, please type "drush help upe" for help or refer to the documentation.', 'cancel');
    return;
  }

  drush_print(' - If you use this module, you are conscience what you are doing. You are the responsible of your work');
  drush_print(' - Please backup your database before any action');
  drush_print(' - Please Read the documentation before any action');
  drush_print(' - Do not use this module on multi sites.');
  drush_print(' - Before a new update, Remove old backuped data if any (Using : drush upe --clean).');

  // Backup database.
  if (!drush_get_option('nobackup')) {
    $db_backup_file = "backup_" . date("ymd-his") . ".sql.gz";
    drush_print("Backup database to : $db_backup_file");
    drush_print("To restore, run : gunzip < $db_backup_file | drush sqlc ");
    @exec('drush cr');
    @exec('drush sql-dump --gzip > ' . $db_backup_file);
  }

  // Basic entity update.
  if (drush_get_option('basic')) {
    if (drush_confirm('Are you sure you want update entities ?')) {
      $res = EntityUpdate::basicUpdate(drush_get_option('force'));
      drush_log('Basic entities update', $res ? 'ok' : 'error');
    }
    return;
  }

  // Copy and delete entities.
  if (drush_get_option('bkpdel')) {
    if (drush_confirm('Are you sure you want copy entities to update in to backup database and delete entities ?')) {
      $res = EntityUpdate::entityUpdateDataBackupDel(EntityUpdate::getEntityTypesToUpdate($type), $type);
      drush_log('End of entities copy and delete process', $res ? 'ok' : 'error');
    }
    return;
  }

  // Update all entities.
  if (drush_get_option('all')) {
    if ($type) {
      drush_log("The option --all and a type has specified, please remove a one.", 'cancel');
      return;
    }

    if (drush_confirm('Are you sure you want update all entities ?')) {
      $res = EntityUpdate::safeUpdateMain();
      drush_log('End of entities update process', $res ? 'ok' : 'error');
    }
    return;
  }
  elseif ($type) {
    // Update a selected entity type.
    try {
      if ($entity_type = entity_update_get_entity_type($type)) {
        // Update the entity type.
        $res = EntityUpdate::safeUpdateMain($entity_type);
        drush_log('End of entities update process for : ' . $type, $res ? 'ok' : 'error');
        return;
      }
    }
    catch (Exception $e) {
      drush_log($e->getMessage(), 'error');
    }
    drush_log("Entity type update Error : $type", 'error');
    return;
  }
}

/**
 * Call back function of entity-check.
 */
function drush_entity_update_entity_check($type = NULL) {

  // Options which hasn't need to have entity type.
  if (drush_get_option('types')) {
    // Display entity types list.
    EntityCheck::getEntityTypesList($type);
    return;
  }

  // Options need to have an entity type.
  if ($type) {

    if (drush_get_option('list')) {
      // Display entity types list.
      $length = drush_get_option('length') ?: 10;
      EntityCheck::getEntityList($type, drush_get_option('start'), $length);
      return;
    }

    // Default action. Show the summary of the entity type.
    EntityUpdatePrint::displaySummery($type);
    return;
  }

  drush_log('No option specified, please type "drush help upec" for help or refer to the documentation.', 'cancel');
}

/**
 * Check options validity.
 */
function _drush_entity_update_checkoptions(array $options) {
  // Check commands list.
  foreach ($options as $option) {
    if (drush_get_option($option)) {
      return TRUE;
    }
  }
  return FALSE;
}
