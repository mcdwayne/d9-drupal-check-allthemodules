<?php

/**
 * @file
 * Batch functions.
 */

use Drupal\Core\Url;
use Drupal\dcat_import\DcatImportLogMigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Batch callback, run import on migration.
 *
 * @param string $id
 *   The migration id.
 * @param array $context
 *   Batch context.
 */
function dcat_import_batch_import($id, array $context) {
  $manager = \Drupal::service('plugin.manager.migration');
  $plugins = $manager->createInstances([$id]);
  /* @var $migration \Drupal\migrate\Plugin\MigrationInterface */
  $migration = $plugins[$id];

  $log = new DcatImportLogMigrateMessage();
  $executable = new MigrateExecutable($migration, $log);
  $executable->import();
}

/**
 * Batch callback finish, redirect when done.
 */
function dcat_import_batch_import_finish($success, $results, $operations) {
  return new RedirectResponse(Url::fromRoute('entity.dcat_source.collection')->toString());
}
