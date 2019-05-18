<?php

/**
 * @file
 * Command-line tools to aid performing and developing migrations.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dcat_import\DcatImportDrushLogMigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
/**
 * Implements hook_drush_command().
 */
function dcat_import_drush_command() {
  return [
    'dcat-import' => [
      'description' => 'Import one or more DCAT sources.',
      'options' => [
        'all' => 'Process all DCAT sources.',
      ],
      'arguments' => [
        'source' => 'ID of source(s) to perform. Delimit multiple using commas.',
      ],
      'examples' => [
        'dcat-import --all' => 'Perform all imports',
        'dcat-import beer,wine' => 'Import beer and wine feeds',
      ],
      'drupal dependencies' => ['dcat_import'],
      'aliases' => ['di'],
    ],
  ];
}

/**
 * Import DCAT source.
 *
 * @param string $import_sources
 *   ID of source(s) to perform. Delimit multiple using commas.
 */
function drush_dcat_import($import_sources = '') {
  $all = drush_get_option('all');
  if (!$all && !$import_sources) {
    drush_set_error('DCAT_ERROR', dt('You must specify --all or one or more dcat source names separated by commas'));
    return;
  }

  $sources = _drush_dcat_import_load_sources($import_sources, $all);
  if (empty($sources)) {
    drush_log(dt('No DCAT sources found.'), 'error');
    return;
  }

  foreach ($sources as $source) {
    _drush_dcat_import_import_source($source);
  }
}

/**
 * Load DCAT sources.
 *
 * @param string $import_sources
 *   ID of source(s) to perform. Delimit multiple using commas.
 * @param bool $all
 *   Boolean to load all sources.
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   DCAT sources.
 */
function _drush_dcat_import_load_sources($import_sources, $all) {
  $storage = \Drupal::entityTypeManager()->getStorage('dcat_source');

  if ($all) {
    return $storage->loadMultiple();
  }

  $source_ids = explode(',', Unicode::strtolower($import_sources));
  if (empty($source_ids)) {
    return [];
  }
  return $storage->loadMultiple($source_ids);
}

/**
 * Actual import.
 *
 * @param \Drupal\Core\Entity\EntityInterface $source
 *   The DCAT source to import.
 */
function _drush_dcat_import_import_source(EntityInterface $source) {
  $start_date = time();
  $id = 'dcat_import_' . $source->id() . '_dataset';

  $manager = \Drupal::service('plugin.manager.migration');
  $migrations = $manager->createInstances([]);
  /* @var $migration \Drupal\migrate\Plugin\MigrationInterface */
  $migration = $migrations[$id];
  $ids = $migration->get('requirements');
  $ids[] = $id;

  foreach ($ids as $id) {
    $log = new DcatImportDrushLogMigrateMessage(\Drupal::database(), $start_date, $source->id(), $id);
    $executable = new MigrateExecutable($migrations[$id], $log);
    $executable->import();
  }
}
