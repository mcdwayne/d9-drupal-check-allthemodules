<?php

namespace Drupal\agreement\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Agreement migrate destination plugin.
 *
 * @MigrateDestination(
 *   id = "agreement",
 *   destination_module = "agreement",
 * )
 */
class Agreement extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['id' => ['type' => 'integer']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'id' => $this->t('Unique Identifier'),
      'type' => $this->t('Agreement type name'),
      'uid' => $this->t('User Identifier'),
      'sid' => $this->t('Session Identifier'),
      'agreed' => $this->t('Agreed?'),
      'agreed_date' => $this->t('Agreement timestamp'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = array_intersect_key($row->getDestination(), $this->fields());

    try {
      $status = $this->connection->merge('agreement')
        ->key('id')
        ->fields($values)
        ->execute();
    }
    catch (DatabaseExceptionWrapper $e) {
      throw new MigrateSkipProcessException($e->getMessage());
    }

    return $status ? [$row->getDestinationProperty('id')] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $db_key = !empty($configuration['database_key']) ? $configuration['database_key'] : NULL;
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      Database::getConnection('default', $db_key)
    );
  }

}
