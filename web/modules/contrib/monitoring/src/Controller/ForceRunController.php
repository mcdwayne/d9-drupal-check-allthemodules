<?php

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ForceRunController extends ControllerBase {

  /**
   * The sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * The sensor runner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorSettingsForm object.
   *
   * @param \Drupal\monitoring\SensorRunner $sensor_runner
   *   The sensor runner service.
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorRunner $sensor_runner, SensorManager $sensor_manager) {
    $this->sensorManager = $sensor_manager;
    $this->sensorRunner = $sensor_runner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_runner'),
      $container->get('monitoring.sensor_manager')
    );
  }

  /**
   * Force runs all sensors.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function forceRunAll() {
    $this->sensorRunner->resetCache();
    drupal_set_message($this->t('Force run of all cached sensors executed.'));
    return $this->redirect('monitoring.sensor_list');
  }

  /**
   * Force runs a single sensor.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $monitoring_sensor_config
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function forceRunSensor(SensorConfig $monitoring_sensor_config) {

    $this->sensorRunner->resetCache(array($monitoring_sensor_config->id()));
    drupal_set_message($this->t('Force run of the sensor @name executed.', array('@name' => $monitoring_sensor_config->getLabel())));
    return $this->redirect('monitoring.sensor_list');
  }
}
