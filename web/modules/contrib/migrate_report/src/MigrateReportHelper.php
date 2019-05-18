<?php

namespace Drupal\migrate_report;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Provides helpers for migrate report module.
 */
class MigrateReportHelper {

  /**
   * Checks if a report can be generated.
   *
   * @param string|null $path
   *   (optional) The destination path. If not passed the configured path will
   *   be used.
   *
   * @return true|string[]
   *   Either TRUE, if the report can be generated, or a list of reasons for not
   *   being able to generate the report.
   */
  public static function canGenerate($path = NULL) {
    $reasons = [];

    $config = \Drupal::config('migrate_report.config');
    $report_dir = $path ?: $config->get('report_dir');
    if (!file_prepare_directory($report_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $reasons[] = t("Directory %dir doesn't exist or is not writable.", ['%dir' => $report_dir]);
    }

    $key_value = \Drupal::keyValue('migrate_last_imported');
    if (!$key_value->getAll()) {
      $reasons[] = t("No migration has ran.");
    }

    return empty($reasons) ? TRUE : $reasons;
  }

  /**
   * Generates a new report based on the last migration run.
   *
   * @param string|null $path
   *   (optional) The destination path. If not passed the configured path will
   *   be used.
   *
   * @return string|null
   *   The generated file or NULL on error.
   */
  public static function generate($path = NULL) {
    $last_imported = \Drupal::keyValue('migrate_last_imported')->getAll();

    asort($last_imported);
    $migration_list = array_keys($last_imported);

    $last_run = max($last_imported) / 1000;

    $migrations = \Drupal::service('plugin.manager.migration')
      ->createInstances('');
    ksort($migrations);

    $path = $path ?: \Drupal::config('migrate_report.config')->get('report_dir');
    $file = rtrim($path, '/\/');
    $file .= DIRECTORY_SEPARATOR . date('Y-m-d H:i', $last_run) . '.txt';
    $db = \Drupal::database();

    $table = (new Table(new StreamOutput(fopen($file, 'w'))))
      ->setHeaders([t('Source ID(s)'), t('Message')]);

    $count = 1;
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
    foreach ($migrations as $migration_id => $migration) {
      if (!$migrated = in_array($migration_id, $migration_list)) {
        $label = t('@label (@id) not migrated yet', [
          '@label' => $migration->label(),
          '@id' => $migration_id,
        ]);
      }
      else {
        $map = $migration->getIdMap();
        $imported = $map->importedCount();
        $source_plugin = $migration->getSourcePlugin();
        $source_rows = $source_plugin->count();
        if ($source_rows == -1) {
          $source_rows = t('N/A');
          $unprocessed = t('N/A');
        }
        else {
          $unprocessed = $source_rows - $map->processedCount();
        }

        $label = t('@label (@id) on @time: @total total, @imported imported, @unprocessed unprocessed', [
          '@label' => $migration->label(),
          '@id' => $migration_id,
          '@time' => date('Y-m-d H:i', $last_imported[$migration_id] / 1000),
          '@total' => $source_rows,
          '@imported' => $imported,
          '@unprocessed' => $unprocessed,
        ]);
      }

      $cell = new TableCell((string) $label, ['colspan' => 2]);
      $table->addRow([$cell]);

      if ($migrated) {
        $keys = [];
        $delta = 1;
        foreach ($source_plugin->getIds() as $id => $info) {
          $keys[$id] = 'sourceid' . $delta++;
        }
        $query = $db->select("migrate_message_{$migration_id}", 'message')
          ->fields('message', ['level', 'message'])
          ->fields('map', array_values($keys));
        $query->leftJoin("migrate_map_{$migration_id}", 'map', 'message.source_ids_hash = map.source_ids_hash');
        $rows = $query
          ->execute()
          ->fetchAll();

        if ($rows) {
          foreach ($rows as $delta => $item) {
            $ids = [];
            $first_key = reset($keys);
            if ($item->$first_key !== NULL) {
              foreach ($keys as $id => $key) {
                $ids[] = $item->$key;
              }
            }
            $message = preg_replace('| \([^\)].*\:\d+\)$|', '', $item->message);
            $table->addRow([implode("\n", $ids), wordwrap($message)]);
          }
        }
        else {
          $table->addRow([new TableCell((string) t('No errors.'), ['colspan' => 2])]);
        }
      }

      if ($count < count($migrations)) {
        $table->addRow(new TableSeparator());
      }

      $count++;
    }
    $table->render();

    return $file;
  }

}
