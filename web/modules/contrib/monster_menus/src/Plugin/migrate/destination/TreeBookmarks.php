<?php

namespace Drupal\monster_menus\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @MigrateDestination(
 *   id = "mm_tree_bookmarks"
 * )
 */
class TreeBookmarks extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The database interface.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param Connection $database
   *   The database interface.
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
  public function import(Row $row, array $old_destination_id_values = array()) {
    $keys = [
      'uid' => $row->getDestinationProperty('uid'),
      'type' => $row->getDestinationProperty('type'),
      'weight' => $row->getDestinationProperty('weight'),
    ];
    $this->database->merge('mm_tree_bookmarks')
      ->keys($keys)
      ->fields(['data' => $row->getDestinationProperty('data')])
      ->execute();
    return $keys;
  }

  /**
   * @inheritDoc
   */
  public function rollback(array $destination_identifier) {
    $delete = $this->database->delete('mm_tree_bookmarks');
    foreach ($destination_identifier as $key => $value) {
      $delete->condition($key, $value);
    }
    $delete->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'uid' => $this->t('User ID'),
      'type' => $this->t('Type of data'),
      'weight' => $this->t('Bookmark weight'),
      'data' => $this->t('Misc. data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'unsigned' => TRUE,
        'alias' => 'b',
      ],
      'type' => [
        'type' => 'string',
        'alias' => 'b',
      ],
      'weight' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
