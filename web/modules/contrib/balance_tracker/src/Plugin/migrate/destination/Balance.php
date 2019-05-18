<?php

namespace Drupal\balance_tracker\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Destination for user balance.
 *
 * @MigrateDestination(
 *   id = "user_balance"
 * )
 */
class Balance extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Balance destination migration plugin.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
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
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['bid' => ['type' => 'integer']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'bid' => $this->t('Balance ID'),
      'uid' => $this->t('User ID'),
      'timestamp' => $this->t('Timestamp'),
      'type' => $this->t('Type'),
      'message' => $this->t('Message'),
      'amount' => $this->t('Amount'),
      'balance' => $this->t('Balance'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $destination = $row->getDestination();
    $fields = array(
      'bid' => $destination['bid'],
      'uid' => $destination['uid'],
      'timestamp' => $destination['timestamp'],
      'type' => $destination['type'],
      'message' => $destination['message'],
      'amount' => $destination['amount'],
      'balance' => $destination['balance'],
    );
    // Update rows that already have the specified balance ID.
    $existing = (bool) $this->database
      ->select('balance_items', 'b')
      ->fields('b', ['bid'])
      ->condition('bid', (int) $destination['bid'])
      ->execute()
      ->fetchAll();
    if ($existing) {
      $this->database
        ->update('balance_items')
        ->fields($fields)
        ->condition('bid', $destination['bid'])
        ->execute();
      return [$destination['bid']];
    }
    else {
      $this->database
        ->insert('balance_items')
        ->fields($fields)
        ->execute();
      return [$this->database->nextId()];
    }
  }

}
