<?php

namespace Drupal\cmlmigrations\Service;

/**
 * Get MigrateService.
 */
class MigrateService implements MigrateServiceInterface {

  /**
   * Get migrations.
   */
  public static function getCmlGroup() {
    $migrations = FALSE;
    $manager = FALSE;
    try {
      $manager = \Drupal::service('plugin.manager.migration');
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if ($manager) {
      $plugins = $manager->createInstances([]);
      if (!empty($plugins)) {
        foreach ($plugins as $id => $migration) {
          $migrations['status'] = TRUE;
          if ($migration->migration_group == 'cml') {
            $source_plugin = $migration->getSourcePlugin();
            $map = $migration->getIdMap();
            if ($migration->getStatusLabel() != 'Idle') {
              $migrations['status'] = FALSE;
            }
            $migrations['list'][$id] = [
              'id' => $migration->id(),
              'label' => $migration->label(),
              'group' => $migration->get('migration_group'),
              'status' => $migration->getStatusLabel(),
              'total' => $source_plugin->count(),
              'imported' => (int) $map->importedCount(),
              'unprocessed' => $source_plugin->count() - (int) $map->importedCount(),
              'messages' => $map->messageCount(),
              'last' => \Drupal::keyValue('migrate_last_imported')->get($migration->id(), FALSE),
            ];
          }
        }
      }
    }
    return $migrations;
  }

}
