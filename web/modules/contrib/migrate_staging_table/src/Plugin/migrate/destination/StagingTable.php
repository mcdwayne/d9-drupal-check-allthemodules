<?php

namespace Drupal\migrate_staging_table\Plugin\migrate\destination;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a migration destination to store items in a database staging table.
 *
 * @MigrateDestination(
 *   id = "staging_table",
 * )
 */
class StagingTable extends DestinationBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Drupal database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * The time service.
   *
   * @var TimeInterface;
   */
  protected $datetime;

  /**
   * Staging constructor.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              Connection $database,
                              TimeInterface $datetime,
                              MigrationInterface $migration) {
    $this->database = $database;
    $this->datetime = $datetime;

    $this->supportsRollback = TRUE;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition,
                                MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('datetime.time'),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'unsigned' => FALSE,
        'size' => 'big',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if (empty($this->configuration['table'])) {
      throw new MigrateException('Missing parameter \'table\'');
    }

    $table_name = $this->configuration['table'];

    if (empty($this->configuration['fields'])) {
      throw new MigrateException('Missing parameter \'fields\'');
    }

    $fields = $this->configuration['fields'];

    if ($this->database->schema()->tableExists($table_name)) {
      $this->updateTable($table_name, $fields);
    }
    else {
      $this->createTable($table_name, $fields);
    }

    // We need an ID destination property.
    if (!$row->hasDestinationProperty('id')) {
     throw  new MigrateException('Missing destination property \'ID\'');
    }
    $id = $row->getDestinationProperty('id');

    // Get all the destination values to insert.
    $values = [];
    foreach (array_keys($fields) as $field_name) {
      if ($row->hasDestinationProperty($field_name)) {
        $values[$field_name] = $row->getDestinationProperty($field_name);
      }
    }

    // Then insert them.
    $this->mergeRecord($id, $values);

    return [$id];
  }

  protected function createTable($table_name, $fields) {

    // Add the ID column.
    $fields['id'] = [
      'description' => 'The item ID',
      'type' => 'int',
      'size' => 'big',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ];

    // And the created column.
    $fields['created'] = [
      'description' => 'The UNIX time stamp representing when item was created.',
      'type' => 'int',
      'unsigned' => TRUE,
      'disp-size' => 11,
    ];

    // Get the index configuration.
    $indexes = empty($this->configuration['indexes']) ? [] : $this->configuration['indexes'];
    $indexes['dm_id_created'] = ['id', 'created'];

    // Build the schema.
    $schema = [
      'description' => empty($this->configuration['description']) ? '' : $this->configuration['description'],
      'fields' => $fields,
      'primary key' => ['id'],
      'indexes' => $indexes,
    ];

    $this->database->schema()->createTable($table_name, $schema);
  }

  protected function updateTable($table_name, $fields) {
    foreach ($fields as $field_name => $field_schema) {
      if ($this->database->schema()->fieldExists($table_name, $field_name)) {
        //
        // Skip any fields which already exist.
        //
        // Migration authors will either need to rollback and delete their
        // migration table, or update the column definition in the database
        // manually.
        //
        continue;
      }

      $this->database->schema()->addField($table_name, $field_name, $field_schema);
    }
  }

  /**
   * Saves fields to the database.
   *
   * @param int $id
   *   The item ID.
   * @param array $fields
   *   An array of field names and values.
   *
   * @throws \Exception
   */
  protected function mergeRecord($id, $fields) {

    $fields['created'] = $this->datetime->getRequestTime();
    
    $this->database->merge($this->configuration['table'])
      ->keys([
        'id' => $id,
      ])
      ->fields($fields)
      ->execute();
  }
}
