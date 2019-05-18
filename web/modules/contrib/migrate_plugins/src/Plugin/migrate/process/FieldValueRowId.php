<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'FieldValueRowId' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "field_value_row_id",
 *   handle_multiples = TRUE
 * )
 */
class FieldValueRowId extends ProcessPluginBase {

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $this->migration->getSourcePlugin();
    $id_keys = array_keys($source->getIds());

    // Set the row IDS on each field value array item.
    foreach ($value as $delta => $value_item) {
      foreach ($id_keys as $id_key) {
        if ($id_value = $row->getSourceProperty($id_key)) {
          $value[$delta][$id_key] = $id_value;
        }
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
