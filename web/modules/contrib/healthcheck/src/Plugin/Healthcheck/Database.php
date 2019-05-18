<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "database",
 *  label = @Translation("Database"),
 *  description = "Checks the database engine.",
 *  tags = {
 *   "performance",
 *  }
 * )
 */
class Database extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * CacheBackend constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    // Get the database information.
    // We aren't using a `use` here because it would collide with this class's name.
    $db_info = \Drupal\Core\Database\Database::getConnectionInfo();

    // Get the DB name and driver from the info.
    $db_name = empty($db_info['default']['database']) ? NULL : $db_info['default']['database'];
    $driver = empty($db_info['default']['driver']) ? NULL : $db_info['default']['driver'];

    // Only check MySQL databases.
    if (!empty($db_name) && $driver == 'mysql') {
      $findings += $this->getFindingsByTable($db_name);
    }
    else {
      $findings[] = new Finding(FindingStatus::NOT_PERFORMED, $this, 'database.myisam', $this->t(
        'Could not acquire database information.'
      ));
    }

    return $findings;
  }

  /**
   * Get the findings for each table.
   *
   * @param string $db_name
   *   The database name as a string.
   *
   * @return array
   *   Gets the findings for each table.
   */
  protected function getFindingsByTable($db_name) {
    $findings = [];
    $results = $this->getTableByEngine($db_name);

    // If there are MyISAM tables, they should be converted to InnoDB.
    if (isset($results['MyISAM'])) {
      $findings[] = $this->actionRequested($this->getPluginId() . '.myisam', [
        'tables' => $results['MyISAM'],
        'table_list' => implode(', ', $results['MyISAM']),
      ]);
    }
    else {
      $findings[] = $this->noActionRequired($this->getPluginId() . '.myisam');
    }

    return $findings;
  }

  /**
   * Gets a list of database engines and their tables.
   *
   * @param $db_name
   *   The database name as a string.
   *
   * @return array
   */
  protected function getTableByEngine($db_name) {
    $engines = [];

    $tables = $this->getTables($db_name);

    foreach ($tables as $table_name => $engine) {
      $engines[$engine][] = $table_name;
    }

    return $engines;

  }

  /**
   * Get the database engine keyed by table name.
   *
   * @param string $db_name
   *   The database name as a string.
   *
   * @return array
   *   An array of database engines keyed by table name.
   */
  protected function getTables($db_name) {
    // Query the table information schema to get the engine.
    $query = $this->database->query('
        SELECT TABLE_NAME as table_name,
               ENGINE as engine
          FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLES.table_schema = :database_name
      ', [
      ':database_name' => $db_name,
    ]);

    // Get the results
    $results = $query->fetchAllKeyed();

    return empty($results) ? [] : $results;
  }
}
