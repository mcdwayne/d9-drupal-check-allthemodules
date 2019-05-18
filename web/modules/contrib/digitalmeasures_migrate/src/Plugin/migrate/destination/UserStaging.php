<?php

namespace Drupal\digitalmeasures_migrate\Plugin\migrate\destination;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination to stage DM user IDs to a database table.
 *
 * While you can query Digital Measures for all users in your schema, it is
 * often faster to query only usernames and IDs, then write a succeeding
 * migration to import each user individually. This plugin provides a
 * destination to stage usernames and IDs to the database.
 *
 * @MigrateDestination(
 *   id = "digitalmeasures_api_user_staging"
 * )
 *
 */
class UserStaging extends DestinationBase implements ContainerFactoryPluginInterface {

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
   * DigitalMeasuresApiProfile constructor.
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
      'userId' => [
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
    return [
      'userId' => $this->t('The Digital Measures user ID'),
      'username' => $this->t('The Digital Measures username'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Get the user ID and name from the pipeline.
    $userId = $row->getDestinationProperty('userId');
    $username = $row->getDestinationProperty('username');

    $this->mergeRecord($userId, $username);

    return [$userId];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // Get the dest ID names.
    $column_names = array_keys($this->getIds());

    // Delete profile fragments from our table...
    $query = $this->database->delete('digitalmeasures_migrate_usernames');

    // ..which matching IDs.
    foreach ($column_names as $column_name) {
      $query->condition($column_name, $destination_identifier[$column_name]);
    }

    $query->execute();
  }

  /**
   * Saves a user ID/name pair to the database.
   *
   * @param int $userId
   *   The user ID.
   * @param username
   *   The username.
   *
   * @throws \Exception
   */
  protected function mergeRecord($userId, $username) {
    $this->database->merge('digitalmeasures_migrate_usernames')
      ->keys([
        'userId' => $userId,
      ])
      ->fields([
        'username' => $username,
        'created' => $this->datetime->getRequestTime(),
      ])
      ->execute();
  }

}
