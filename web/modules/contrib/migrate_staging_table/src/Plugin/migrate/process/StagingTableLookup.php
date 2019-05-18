<?php

namespace Drupal\migrate_staging_table\Plugin\migrate\process;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateException;

/**
 * Fetches a column value from a database staging table given an ID.
 *
 * @MigrateProcessPlugin(
 *   id = "staging_table_lookup"
 * )
 */
class StagingTableLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * StagingTableLookup constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Return NULL if no value.
    if (empty($value)) {
      return NULL;
    }

    // We need the 'table' parameter.
    if (empty($this->configuration['table'])) {
      throw new MigrateException('staging_table_lookup: Missing parameter \'table\'');
    }

    $table_name = $this->configuration['table'];

    // And the 'field' parameter.
    if (empty($this->configuration['field'])) {
      throw new MigrateException('staging_table_lookup: Missing parameter \'field\'');
    }

    $field_name = $this->configuration['field'];

    // Get the ID and the XML field from the staging table...
    $query = $this->database->select($table_name, 'stl')
      ->fields('stl', [
        'id',
        $field_name,
      ]);

    // Get the results.
    $result = $query->execute()->fetchAllAssoc('id');

    // If no results, return NULL so Migrate doesn't barf.
    if (empty($result)) {
      return NULL;
    }

    // Otherwise return the requested field.
    $item = reset($result);
    return $item->{$field_name};
  }

}
