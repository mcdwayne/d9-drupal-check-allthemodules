<?php
/**
 * @file
 * Contains \Drupal\monitoring\Controller\RequirementsIgnore.
 */

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RequirementsIgnore extends ControllerBase {

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
   * Adds sensor key into the excluded list.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $monitoring_sensor_config
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function sensorIgnoreKey(SensorConfig $monitoring_sensor_config, $key) {
    if (!in_array($key, $monitoring_sensor_config->settings['exclude_keys'])) {
      $monitoring_sensor_config->settings['exclude_keys'][] = $key;
      $monitoring_sensor_config->save();
      drupal_set_message($this->t('Added the sensor %name (%key) into the excluded list.', array(
        '%name' => $monitoring_sensor_config->getLabel(),
        '%key' => $key,
      )));
    }
    $url = $monitoring_sensor_config->urlInfo('details-form');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Removes sensor key from the excluded list.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $monitoring_sensor_config
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function sensorUnignoreKey(SensorConfig $monitoring_sensor_config, $key) {
    if (in_array($key, $monitoring_sensor_config->settings['exclude_keys'])) {
      if ($index = array_search($key, $monitoring_sensor_config->settings['exclude_keys'])) {
        unset($monitoring_sensor_config->settings['exclude_keys'][$index]);
      }
      $monitoring_sensor_config->save();
      drupal_set_message($this->t('Removed the sensor %name (%key) from the excluded list.', array(
        '%name' => $monitoring_sensor_config->getLabel(),
        '%key' => $key,
      )));
    }
    $url = $monitoring_sensor_config->urlInfo('details-form');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }
}
