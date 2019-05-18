<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors system memory.
 *
 * @SensorPlugin(
 *   id = "monitoring_system_memory",
 *   label = @Translation("System memory"),
 *   description = @Translation("Monitors system memory."),
 *   addable = TRUE
 * )
 */
class SystemMemorySensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, StateInterface $state_system) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);
    $this->state = $state_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {
    $data = $this->readMemInfo();
    $data = explode("\n", $data);

    $meminfo = [];
    foreach ($data as $line) {
      list($key, $val) = array_pad(explode(':', $line, 2), 2, NULL);
      $meminfo[$key] = explode(' ', trim($val))[0];
    }

    // Evaluates the free memory in percentage.
    if (isset($meminfo['MemAvailable']) && isset($meminfo['MemTotal'])) {
      $memory = NULL;
      if ($this->sensorConfig->getSetting('memory') == 'free') {
        $memory = $meminfo['MemAvailable'];
      }
      else {
        $memory = $meminfo['MemTotal'] - $meminfo['MemAvailable'];
      }

      if ($this->sensorConfig->getSetting('value') == 'percentage') {
        $memory = $memory / $meminfo['MemTotal'] * 100;
      }
      else {
        // Converts memory value from KB to MB.
        $memory /= 1000;
      }
      $sensor_result->setValue((int) $memory);
    }
    else {
      $sensor_result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      $sensor_result->setMessage('This sensor is not supported by your system. It is based on memory information from /proc/meminfo, only provided by UNIX-like systems.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = [
      'settings' => [
        'memory' => 'free',
        'value' => 'percentage',
      ],
    ];
    return $default_config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $memory_track = [
      'free' => $this->t('Free memory'),
      'used' => $this->t('Used memory'),
    ];
    $memory_value = [
      'percentage' => $this->t('Percentage'),
      'megabytes' => $this->t('MB'),
    ];

    $form['memory'] = [
      '#title' => $this->t('Track'),
      '#type' => 'radios',
      '#options' => $memory_track,
      '#required' => TRUE,
      '#default_value' => $this->sensorConfig->getSetting('memory'),
    ];

    $form['value'] = [
      '#title' => $this->t('Report as'),
      '#type' => 'radios',
      '#options' => $memory_value,
      '#required' => TRUE,
      '#default_value' => $this->sensorConfig->getSetting('value'),
    ];
    return $form;
  }

  /**
   * Reads the memory info file.
   *
   * @return string $data
   *   The content of /proc/meminfo.
   */
  protected function readMemInfo() {
    // Checks if any test data is available. The state is solely used to
    // simulate data for tests.
    if ($this->state->get('monitoring.test_meminfo') !== NULL) {
      return $this->state->get('monitoring.test_meminfo');
    }
    else {
      return file_get_contents("/proc/meminfo");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $data = $this->readMemInfo();
    $data = explode("\n", $data);

    $rows = [];
    foreach ($data as $line) {
      list($key, $val) = array_pad(explode(':', $line, 2), 2, NULL);
      if (trim($key)) {
        $rows[] = [
          'type' => $key,
          'memory' => trim($val),
        ];
      }
    }

    $output = NULL;
    if (count($rows) > 0) {
      $header = [
        'type' => $this->t('Type'),
        'memory' => $this->t('Memory'),
      ];
      $output['memory_info'] = [
        '#type' => 'verbose_table_result',
        '#title' => $this->t('Memory information'),
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    return $output;
  }

}
