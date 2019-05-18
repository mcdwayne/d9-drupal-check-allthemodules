<?php


namespace Drupal\digitalmeasures_migrate\Plugin\migrate\source;


use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\digitalmeasures_migrate\DigitalMeasuresApiServiceInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a source for DM usernames and IDs from the staging table.
 *
 * @MigrateSource(
 *   id = "digitalmeasures_api_user_staging",
 *   source_module = "digitalmeasures_migrate"
 * )
 */
class UserStaging extends SqlBase implements ContainerFactoryPluginInterface {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

    /**
   * The Digital Measures API service.
   *
   * @var \Drupal\digitalmeasures_migrate\DigitalMeasuresApiServiceInterface
   */
  protected $digitalMeasuresApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              MigrationInterface $migration,
                              StateInterface $state,
                              Connection $database,
                              DigitalMeasuresApiServiceInterface $digitalMeasuresApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);

    // Since we're drawing from the profile staging table, we set the connection
    // explicitly to the Drupal 8 database. This avoids us needing to specify
    // the database key via the group or migration config.
    $this->database = $database;
    $this->digitalMeasuresApi = $digitalMeasuresApi;
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
      $container->get('database'),
      $container->get('digitalmeasures.api')
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
  public function fields() {
    return [
      'userId' => $this->t('The Digital Measures user ID'),
      'xml' => $this->t('The Digital Measures user name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('digitalmeasures_migrate_usernames', 'pf')
      ->fields('pf', [
        'userId',
        'username',
      ]);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $username = $row->getSourceProperty('username');

    $endpoint = isset($this->configuration['beta']) ? $this->configuration['beta'] : -1;

    try {
      $xml = $this->digitalMeasuresApi->getProfile($username, $this->configuration['schema_key'], $endpoint);
    }
    catch (GuzzleException $e) {
      // Output a helpful error message, as some profiles are hugemendic.
      echo "Couldn't fetch $username. Try increasing 'http_client_config.timeout'." . PHP_EOL;

      // Skip the row so that the migrations keep running.
      // Skipped entries will be re-tried on the next run.
      return FALSE;
    }

    $row->setSourceProperty('profile_xml', $xml);

    return parent::prepareRow($row);
  }

}