<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors system load time average.
 *
 * @SensorPlugin(
 *   id = "system_load",
 *   label = @Translation("System load"),
 *   description = @Translation("Monitors system load average."),
 *   addable = TRUE
 * )
 */
class SystemLoadSensorPlugin extends SensorPluginBase {

  /**
   * Holds the state system instance.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The load average of the system provided by sys_getloadavg().
   *
   * @var array
   */
  protected $systemLoadAverage;

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['average_monitored'] = [
      '#type' => 'select',
      '#title' => $this->t('Average monitored'),
      '#options' => [
        '0' => $this->t('1 minute'),
        '1' => $this->t('5 minutes'),
        '2' => $this->t('15 minutes'),
      ],
      '#default_value' => $this->sensorConfig->getSetting('average_monitored'),
      '#description' => $this->t('You can select which average will be monitored. Its value will be multiplied by 100 where 100% means that a single CPU is used. For more information check <a href="https://en.wikipedia.org/wiki/Load_(computing)">this</a>.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $load_average = $this->getLoadAverage();
    if (!$load_average) {
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      $result->setMessage('Could not get the required information, sys_getloadavg() is not available.');
      return;
    }
    else {
      // If setting is set use it for selecting the average.
      $setting = $this->sensorConfig->getSetting('average_monitored');
      $average = $load_average[$setting];
      // Convert average to int.
      $average = (int) ($average * 100);
      // Set values and status based on the system thresholds.
      $result->setValue($average);
      $result->addStatusMessage(implode($load_average, ', '));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'value_label' => '% Average',
      'caching_time' => 86400,
      'value_type' => 'number',
      'thresholds' => [
        'type' => 'exceeds',
        'warning' => 80,
        'critical' => 100,
      ],
      'settings' => [
        'average_monitored' => '1',
      ],
    ];
  }

  /**
   * Gets the load average for the selected setting.
   *
   * @return array|NULL
   *   The load averages or NULL if the method does not exist.
   */
  protected function getLoadAverage() {
    if ($data = $this->state->get('monitoring.test_load_average')) {
      return $data;
    }
    if (!function_exists('sys_getloadavg')) {
      return NULL;
    }
    else {
      return sys_getloadavg();
    }
  }

}
