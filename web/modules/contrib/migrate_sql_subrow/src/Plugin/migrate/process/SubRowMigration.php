<?php

namespace Drupal\migrate_sql_subrow\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calculates the value of a property based on a previous sub row migration.
 *
 * Gets multiple results if available based on one or more id columns. May
 * (should) also work with normal migrations but untested. See also
 * @link https://www.drupal.org/node/2149801 Online handbook documentation for migration process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "sub_row_migration"
 * )
 */
class SubRowMigration extends Migration implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migration_ids = $this->configuration['migration'];
    if (!is_array($migration_ids)) {
      $migration_ids = [$migration_ids];
    }
    if (!is_array($value)) {
      $value = [$value];
    }
    $this->skipOnEmpty($value);
    $self = FALSE;
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
    $destination_ids = NULL;
    $source_id_values = [];
    $migrations = $this->migrationPluginManager->createInstances($migration_ids);
    foreach ($migrations as $migration_id => $migration) {
      if ($migration_id == $this->migration->id()) {
        $self = TRUE;
      }
      if (isset($this->configuration['source_ids'][$migration_id])) {
        $configuration = [
          'source' => $this->configuration['source_ids'][$migration_id]
        ];
        $source_id_values[$migration_id] = $this->processPluginManager
          ->createInstance('get', $configuration, $this->migration)
          ->transform(NULL, $migrate_executable, $row, $destination_property);
      }
      else {
        $source_id_values[$migration_id] = $value;
      }
      // Break out of the loop as soon as a destination ID is found.
      if ($destination_ids = $migration->getIdMap()->lookupDestinationIds($source_id_values[$migration_id])) {
        break;
      }
    }

    if (!$destination_ids && !empty($this->configuration['no_stub'])) {
      return NULL;
    }

    if (!$destination_ids && ($self || isset($this->configuration['stub_id']) || count($migrations) == 1)) {
      // If the lookup didn't succeed, figure out which migration will do the
      // stubbing.
      if ($self) {
        $migration = $this->migration;
      }
      elseif (isset($this->configuration['stub_id'])) {
        $migration = $migrations[$this->configuration['stub_id']];
      }
      else {
        $migration = reset($migrations);
      }
      $destination_plugin = $migration->getDestinationPlugin(TRUE);
      // Only keep the process necessary to produce the destination ID.
      $process = $migration->getProcess();

      // We already have the source ID values but need to key them for the Row
      // constructor.
      $source_ids = $migration->getSourcePlugin()->getIds();
      $values = [];
      foreach (array_keys($source_ids) as $index => $source_id) {
        $values[$source_id] = $source_id_values[$migration->id()][$index];
      }

      $stub_row = new Row($values + $migration->getSourceConfiguration(), $source_ids, TRUE);

      // Do a normal migration with the stub row.
      $migrate_executable->processRow($stub_row, $process);
      $destination_ids = [];
      $id_map = $migration->getIdMap();
      $id_map->setMessage(new MigrateMessage());
      try {
        $destination_ids = $destination_plugin->import($stub_row);
      }
      catch (\Exception $e) {
        $id_map->saveMessage($stub_row->getSourceIdValues(), $e->getMessage());
      }

      if ($destination_ids) {
        $id_map->saveIdMapping($stub_row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
      }
    }
    if ($destination_ids) {
      if (count($destination_ids) == 1) {
        return reset($destination_ids);
      }
      else {
        $destination_ids = array_map(function ($destination_id) {
          if (is_array($destination_id) && count($destination_id) === 1) {
            return reset($destination_id);
          }
        }, $destination_ids);
        return $destination_ids;
      }
    }
  }

}
