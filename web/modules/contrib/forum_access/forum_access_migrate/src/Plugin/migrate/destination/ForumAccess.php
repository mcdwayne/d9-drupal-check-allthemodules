<?php

namespace Drupal\forum_access_migrate\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\destination\Table;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides table destination plugin.
 *
 * Use this plugin for a table not registered with Drupal Schema API.
 *
 * @MigrateDestination(
 *   id = "forum_access",
 *   destination_module = "forum_access"
 * )
 */
class ForumAccess extends Table {

  /**
   * The name of the destination table.
   *
   * @var string
   */
  protected $tableName;

  /**
   * IDMap compatible array of id fields.
   *
   * @var array
   */
  protected $idFields;

  /**
   * Array of fields present on the destination table.
   *
   * @var array
   */
  protected $fields;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $connection);
    $this->dbConnection = $connection;
    $this->tableName = 'forum_access';
    $this->idFields = [
      'tid' => [
        'type' => 'integer',
      ],
      'rid' => [
        'type' => 'string',
      ],
    ];
    $this->fields = [];
    $this->supportsRollback = TRUE;
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

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Migrate moderators.
    module_load_include('inc', 'forum_access', 'includes/forum_access.acl');
    $tid = $row->getSourceProperty('tid');
    $moderators = $row->getSourceProperty('moderators');
    if (!empty($moderators)) {
      $acl_id = forum_access_get_acl($tid, 'moderate');
      foreach ($moderators as $uid) {
        acl_add_user($acl_id, $uid);
      }
    }
    return parent::import($row, $old_destination_id_values);
  }

}
