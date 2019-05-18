<?php


namespace Drupal\digitalmeasures_migrate\Plugin\migrate\source;


use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports profile fragments from the staging table.
 *
 *
 *
 * @MigrateSource(
 *   id = "digitalmeasures_api_profile_fragment",
 *   source_module = "digitalmeasures_migrate"
 * )
 */
class ProfileFragment extends SqlBase implements ContainerFactoryPluginInterface {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              MigrationInterface $migration,
                              StateInterface $state,
                              Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);

    // Since we're drawing from the profile staging table, we set the connection
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
      'id' => $this->t('The profile fragment ID'),
      'userId' => $this->t('The Digital Measures user ID'),
      'category' => $this->t('The profile fragment type'),
      'xml' => $this->t('The XML of the profile fragment'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('digitalmeasures_migrate_profile', 'pf')
      ->fields('pf', [
        'id',
        'userId',
        'category',
        'created',
        'xml',
      ]);

    if (isset($this->configuration['category'])) {
      $query->condition('category', $this->configuration['category']);
    }

    return $query;
  }

}