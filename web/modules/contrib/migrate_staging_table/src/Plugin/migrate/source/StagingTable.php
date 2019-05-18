<?php

namespace Drupal\migrate_staging_table\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateException;

/**
 * Provides a migration source for items loaded into a database staging table.
 *
 * @MigrateSource(
 *   id = "staging_table",
 *   source_module = "migrate_staging_table"
 * )
 */
class StagingTable extends SqlBase implements ContainerFactoryPluginInterface{

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);

    // Since we're drawing from the staging table, we set the connection
    // explicitly to the Drupal 8 database. This avoids us needing to specify
    // the database key via the group or migration config.
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('database')
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
  public function fields() {
    return [
      'id' => $this->t('The item ID'),
      'created' => $this->t('The created date'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->configuration['table'])) {
      throw new MigrateException('staging_table: Missing parameter \'table\'');
    }

    $table_name = $this->configuration['table'];

    $fields = empty($this->configuration['fields']) ? [] : $this->configuration['fields'];

    if (!in_array('id', $fields)) {
      $fields[] = 'id';
    }

    if (!in_array('created', $fields)) {
      $fields[] = 'created';
    }

    $query = $this->select($table_name, 'mst')
      ->fields('mst', $fields);

    if (!empty($this->configuration['conditions'])) {
      foreach ($this->configuration['conditions'] as $condition) {

        $column = $condition['column'];
        $value = $condition['value'];
        $operator = empty($condition['operator']) ? '=' : $condition['operator'];

        $query->condition($column, $value, $operator);
      }
    }

    return $query;
  }

}
