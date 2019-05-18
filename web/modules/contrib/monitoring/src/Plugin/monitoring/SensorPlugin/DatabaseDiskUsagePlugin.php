<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors database disk usage.
 *
 * @SensorPlugin(
 *   id = "database_disk_usage",
 *   label = @Translation("Database Disk Usage"),
 *   description = @Translation("Monitors how much space the database uses."),
 *   addable = FALSE
 * )
 */
class DatabaseDiskUsagePlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * DatabaseDiskUsagePlugin constructor.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
   *   Sensor config object.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state_system
   *   The state storage object.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, StateInterface $state_system, Connection $database) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);

    $this->state = $state_system;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {
    if (!$disk_usage = $this->getDiskUsage()) {
      throw new \RuntimeException($this->t('The disk space usage is not available.'));
    }
    $sensor_result->setValue(number_format($disk_usage, 2));
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];

    $disk_usage = $this->getDiskUsage();
    $database_name = $this->database->getConnectionOptions()['database'];
    $usage_by_table = $this->getDiskUsageByTable($database_name);

    if ($this->sensorConfig->getThresholdValue('warning') && $this->sensorConfig->getThresholdValue('critical') && $disk_usage) {
      $output['database_usage'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Database usage'),
      ];

      $output['database_usage']['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Usage'),
          $this->t('Warning level (@amountMB)', [
            '@amount' => $this->sensorConfig->getThresholdValue('warning'),
          ]),
          $this->t('Critical level (@amountMB)', [
            '@amount' => $this->sensorConfig->getThresholdValue('critical'),
          ]),
        ],
      ];

      $output['database_usage']['table'][0]['usage'] = [
        '#type' => 'item',
        '#plain_text' => number_format($disk_usage, 2) . 'MB',
      ];
      $output['database_usage']['table'][0]['warning'] = [
        '#type' => 'item',
        '#plain_text' => number_format(($disk_usage * 100) / $this->sensorConfig->getThresholdValue('warning'), 2) . '%',
      ];
      $output['database_usage']['table'][0]['critical'] = [
        '#type' => 'item',
        '#plain_text' => number_format(($disk_usage * 100) / $this->sensorConfig->getThresholdValue('critical'), 2) . '%',
      ];
    }

    if ($usage_by_table) {
      $output['tables_usage'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Biggest database tables by size.'),
      ];

      $output['tables_usage']['table'] = [
        '#type' => 'table',
        '#header' => [
          'table_name' => [
            'data' => $this->t('Table name'),
          ],
          'table_rows' => [
            'data' => $this->t('Table rows'),
            'class' => [RESPONSIVE_PRIORITY_MEDIUM],
          ],
          'table_length' => [
            'data' => $this->t('Table length (MB)'),
            'class' => [RESPONSIVE_PRIORITY_LOW],
          ],
          'index_length' => [
            'data' => $this->t('Index length (MB)'),
            'class' => [RESPONSIVE_PRIORITY_LOW],
          ],
          'size' => [
            'data' => $this->t('Size (MB)'),
          ],
        ],
      ];

      foreach ($usage_by_table as $key => $table_info) {
        $output['tables_usage']['table'][$key]['table_name'] = [
          '#type' => 'item',
          '#plain_text' => $table_info->table_name,
        ];
        $output['tables_usage']['table'][$key]['table_rows'] = [
          '#type' => 'item',
          '#plain_text' => $table_info->table_rows,
        ];
        $output['tables_usage']['table'][$key]['table_length'] = [
          '#type' => 'item',
          '#plain_text' => $table_info->table_length,
        ];
        $output['tables_usage']['table'][$key]['index_length'] = [
          '#type' => 'item',
          '#plain_text' => $table_info->index_length,
        ];
        $output['tables_usage']['table'][$key]['size'] = [
          '#type' => 'item',
          '#plain_text' => $table_info->size,
        ];
      }
    }

    return $output;
  }

  /**
   * Gets the database disk space usage in megabytes.
   *
   * @return string|null
   *   Returns the disk usage or NULL.
   */
  protected function getDiskUsage() {
    // Condition is used to simulate data for purpose of testing.
    $test_usage = $this->state->get('monitoring.test_database_disk_usage');
    if (isset($test_usage)) {
      return $test_usage;
    }
    if ($this->database->databaseType() !== 'mysql') {
      throw new \RuntimeException($this->t('The table information is only available for mysql databases.'));
    }
    $result = $this->database->query(
      "SELECT SUM(data_length + index_length + data_free) / 1048576 AS disk_used
      FROM information_schema.tables")
      ->fetch();
    return $result->disk_used;
  }

  /**
   * Gets disk usage by table.
   *
   * Returns number of rows, data memory usage in MB, index memory usage in MB
   * for every table.
   *
   * @param string $database_name
   *   The name of the database.
   * @param int $from
   *   The starting index.
   * @param int $count
   *   The number of records.
   *
   * @throws \RuntimeException
   *   Thrown when the database type is not mysql or the database name is not
   *   set.
   */
  protected function getDiskUsageByTable($database_name, $from = 0, $count = 10) {
    if ($this->database->databaseType() !== 'mysql') {
      throw new \RuntimeException($this->t('The table information is only available for mysql databases.'));
    }

    if (!$database_name) {
      throw new \RuntimeException($this->t('The database name needs to be set.'));
    }

    $query = "
    SELECT table_schema, table_name,
    SUM(table_rows) AS 'table_rows',
    SUM(round(data_length / 1048576, 2)) AS 'table_length',
    SUM(round(index_length / 1048576, 2)) AS 'index_length',
    SUM(round(((data_length + index_length) / 1024 / 1024),2)) AS 'size'
    FROM information_schema.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
    AND table_schema = :database_name
    GROUP BY table_schema, table_name
    ORDER BY size DESC";

    return $this->database->queryRange($query, $from, $count, [':database_name' => $database_name])->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = [
      'value_label' => 'mb',
      'caching_time' => 86400,
      'value_type' => 'number',
      'thresholds' => [
        'type' => 'exceeds',
      ],
    ];
    return $default_config;
  }

}
