<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors disk space usage.
 *
 * @SensorPlugin(
 *   id = "disk_usage",
 *   label = @Translation("Disk Usage"),
 *   description = @Translation("Monitors disk space usage."),
 *   addable = TRUE
 * )
 */
class DiskUsageSensorPlugin extends SensorPluginBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, FileSystemInterface $file_system, StateInterface $state_system) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
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
      $container->get('file_system'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {
    $disk_data = $this->getDiskData();
    $sensor_result->setValue($disk_data['used_space_percent']);
    $sensor_result->addStatusMessage($this->t('@used used of @total available.', [
      '@total' => $disk_data['total_space'],
      '@used' => $disk_data['used_space'],
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory'),
      '#description' => $this->t('A directory of the filesystem or disk partition.'),
      '#default_value' => $this->sensorConfig->getSetting('directory'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'caching_time' => 86400,
      'value_type' => 'number',
      'category' => 'System',
      'value_label' => '%',
      'thresholds' => [
        'type' => 'exceeds',
        'warning' => 80,
        'critical' => 95,
      ],
      'settings' => [
        'directory' => 'public://',
      ],
    ];
  }

  /**
   * Get disk info and calculate percent of used space.
   *
   * @return array
   *   Result info array with 3 items: used_space_percent, used_space,
   *   total_space. They represent used disk space in percents (float),
   *   used disk space (string) and total disk space (string). Used and total
   *   disk space are formatted in human readable size ('KB', 'MB', 'GB', 'TB',
   *   'PB', 'EB','ZB', 'YB').
   *
   * @throws \RuntimeException
   *   Thrown when the directory is not valid.
   */
  protected function getDiskData() {
    // Condition is used to simulate data for purpose of testing.
    if ($data = $this->state->get('monitoring.test_disk_usage')) {
      return $data;
    }
    // Get partition info and calculate used space percent.
    $real_path = $this->fileSystem->realpath($this->sensorConfig->getSetting('directory'));
    if (!$real_path || !is_dir($real_path) || !$total_space = disk_total_space($real_path)) {
      throw new \RuntimeException($this->t('Invalid directory.'));
    }
    else {
      $free_space = disk_free_space($real_path);
      return [
        'used_space_percent' => number_format((1 - $free_space / $total_space) * 100, 2),
        'used_space' => format_size($total_space - $free_space),
        'total_space' => format_size($total_space),
      ];
    }
  }

}
