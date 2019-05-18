<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors the Solr disk usage.
 *
 * @SensorPlugin(
 *   id = "solr_disk_usage",
 *   label = @Translation("Solr Disk Usage"),
 *   description = @Translation("Monitors the Solr disk usage."),
 *   provider = "search_api_solr",
 *   addable = TRUE
 * )
 */
class SolrDiskUsageSensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * The Solr server.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $solrServer;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Instantiates a sensor object.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
   *   Sensor config object.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);

    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    if ($index_size = $this->getSolrIndexSize()) {
      $result->setMessage($index_size);
      // Remove type from string and use just the value in megabytes.
      $index_size = $this->convertToMegabyte($index_size);
      $result->setValue($index_size);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];
    if (!$solr_info = $this->getSolrInfo()) {
      return $output;
    }

    $output['tables_usage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Solr server: @server_name, host: @host, core: @core", [
        '@server_name' => $solr_info['server_name'],
        '@host' => $solr_info['host'],
        '@core' => $solr_info['core'],
      ]),
    ];

    $total_physical_memory = $solr_info['total_physical_memory'];
    $free_physical_memory = $solr_info['free_physical_memory'];
    $physical_memory_usage_percentage = (($total_physical_memory - $free_physical_memory) * 100) / $total_physical_memory;

    $total_swap_memory = $solr_info['total_swap_memory'];
    $free_swap_memory = $solr_info['free_swap_memory'];
    $swap_memory_usage_percentage = (($total_swap_memory - $free_swap_memory) * 100) / $total_physical_memory;

    $output['tables_usage']['table'] = [
      '#type' => 'table',
      '#header' => [
        'index_size' => [
          'data' => $this->t('Index size'),
        ],
        'index_docs' => [
          'data' => $this->t('Indexed docs'),
        ],
        'physical_memory' => [
          'data' => $this->t('Physical memory (@total available)', [
            '@total' => format_size($total_physical_memory),
          ]),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        'swap_space' => [
          'data' => $this->t('Swap memory (@total available)', [
            '@total' => format_size($total_swap_memory),
          ]),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
      ],
    ];

    $output['tables_usage']['table'][0]['index_size'] = [
      '#type' => 'item',
      '#plain_text' => $this->getSolrIndexSize(),
    ];
    $output['tables_usage']['table'][0]['index_docs'] = [
      '#type' => 'item',
      '#plain_text' => $solr_info['indexed_docs'],
    ];
    $output['tables_usage']['table'][0]['physical_memory'] = [
      '#type' => 'item',
      '#plain_text' => $this->t('@used (@percentage%) used', [
        '@used' => format_size($total_physical_memory - $free_physical_memory),
        '@percentage' => number_format($physical_memory_usage_percentage, 2),
      ]),
    ];
    $output['tables_usage']['table'][0]['swap_space'] = [
      '#type' => 'item',
      '#plain_text' => $this->t('@used (@percentage%) used', [
        '@used' => format_size($total_swap_memory - $free_swap_memory),
        '@percentage' => number_format($swap_memory_usage_percentage, 2),
      ]),
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $servers = $this->entityTypeManager->getStorage('search_api_server')
      ->loadByProperties(['backend' => 'search_api_solr', 'status' => TRUE]);

    $options = [];
    foreach ($servers as $server) {
      $options[$server->id()] = $server->label();
    }

    $form['server'] = [
      '#title' => $this->t('Server'),
      '#description' => $this->t('Search API servers that use Solr as backed.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->sensorConfig->getSetting('server'),
    ];

    if (!$options) {
      $form['server']['#description'] = $this->t('There is no search api Solr servers available or enabled.');
    }

    return $form;
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
      'settings' => [
        'server' => '',
      ],
    ];
    return $default_config;
  }

  /**
   * Returns the Solr index size.
   *
   * @return mixed|null
   *   Returns the Solr index size.
   */
  protected function getSolrIndexSize() {
    $index_size = NULL;
    // Condition is used to simulate data for purpose of testing.
    if ($data = $this->state->get('monitoring.test_solr_index_size')) {
      $index_size = $data;
    }
    elseif ($server = $this->getSolrServer()) {
      $index_size = $server->getBackend()->getSolrConnector()->getStatsSummary()['@index_size'];
    }

    return $index_size;
  }

  /**
   * Returns Solr information.
   *
   * @return array
   *   Returns an array with server name, host, core name, physical and swap
   *   memory information.
   */
  protected function getSolrInfo() {
    $info = [];
    // Condition is used to simulate data for purpose of testing.
    if ($data = $this->state->get('monitoring.test_solr_info')) {
      $info = $data;
    }
    elseif ($server = $this->getSolrServer()) {
      /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
      $backend = $server->getBackend();
      $connector = $backend->getSolrConnector();

      $info = [
        'server_name' => $server->label(),
        'host' => $backend->getConfiguration()['connector_config']['host'],
        'core' => $backend->getConfiguration()['connector_config']['core'],
        'total_physical_memory' => $connector->getServerInfo()['system']['totalPhysicalMemorySize'],
        'free_physical_memory' => $connector->getServerInfo()['system']['freePhysicalMemorySize'],
        'total_swap_memory' => $connector->getServerInfo()['system']['totalSwapSpaceSize'],
        'free_swap_memory' => $connector->getServerInfo()['system']['freeSwapSpaceSize'],
        'indexed_docs' => $connector->getLuke()['index']['numDocs'],
      ];
    }

    return $info;
  }

  /**
   * Returns the Solr server if available.
   *
   * @return \Drupal\search_api\ServerInterface|null
   *   Returns the Solr server if available otherwise NULL.
   *
   * @throws \RuntimeException
   *   Thrown when the Solr is not configured the server does not exist or its
   *   not available.
   */
  protected function getSolrServerIfAvailable() {
    if (!$server_name = $this->sensorConfig->getSetting('server')) {
      throw new \RuntimeException($this->t('Solr server is not configured.'));
    }

    if (!$server = $this->entityTypeManager->getStorage('search_api_server')->load($server_name)) {
      throw new \RuntimeException($this->t("Solr server doesn't exist."));
    }

    $solr_connector = $server->getBackend()->getSolrConnector();
    if (!$solr_connector->pingServer()) {
      throw new \RuntimeException($this->t('Server is not available.'));
    }

    if (!$solr_connector->pingCore()) {
      throw new \RuntimeException($this->t('Core is not available.'));
    }

    return $server;
  }

  /**
   * Returns the Solr server.
   *
   * @return \Drupal\search_api\ServerInterface|null
   *   Returns the Solr server or NULL.
   */
  public function getSolrServer() {
    if (!$this->solrServer) {
      $this->solrServer = $this->getSolrServerIfAvailable();
    }
    return $this->solrServer;
  }

  /**
   * Converts human readable size to megabytes.
   *
   * @param string $value
   *   The human readable size (10 MB, 1 gb, 100 bytes) to be converted. There
   *   needs to be a space between the number and type.
   *
   * @return string|null
   *   Returns the megabyte value or NULL.
   */
  public function convertToMegabyte($value) {
    $number = explode(' ', trim($value))[0];
    $type = explode(' ', trim($value))[1];
    switch (strtoupper($type)) {
      case "BYTES":
        return $number / 1024 / 1024;

      case "KB":
        return $number / 1024;

      case "MB":
        return $number;

      case "GB":
        return $number * 1024;

      case "TB":
        return $number * pow(1024, 2);

      case "PB":
        return $number * pow(1024, 3);

      default:
        return NULL;
    }
  }

}
