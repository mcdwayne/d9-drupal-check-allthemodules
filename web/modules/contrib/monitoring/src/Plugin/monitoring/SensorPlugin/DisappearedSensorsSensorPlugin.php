<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DisappearedSensorsSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Monitors if sensors disappeared without prior being disabled.
 *
 * @SensorPlugin(
 *   id = "monitoring_disappeared_sensors",
 *   label = @Translation("Disappeared Sensors"),
 *   description = @Translation("Monitors if sensors disappeared without prior being disabled."),
 *   addable = FALSE
 * )
 *
 * It stores the list of available sensors and their enabled/disabled status
 * and compares it to the current sensor config retrieved via
 * monitoring_sensor_config() callback.
 */
class DisappearedSensorsSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $available_sensors = \Drupal::state()->get('monitoring.available_sensors', array());
    $sensor_config = monitoring_sensor_manager()->getAllSensorConfig();

    $available_sensors = $this->updateAvailableSensorsList($available_sensors, $sensor_config);
    $this->checkForMissingSensors($result, $available_sensors, $sensor_config);
  }

  /**
   * Adds UI to clear the missing sensor status.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    try {
      $result = monitoring_sensor_run($this->sensorConfig->id());
    } catch (\Exception $e) {
      // @todo: Figure out why this happens.
      drupal_set_message($e->getMessage(), 'error');
      return array();
    }
    $form = parent::buildConfigurationForm($form, $form_state);

    if ($result->isCritical()) {
      $form['clear_missing_sensors_wrapper'] = array(
        '#type' => 'fieldset',
        '#title' => t('Missing sensors'),
        '#description' => t('This action will clear the missing sensors and the critical sensor status will go away.'),
        '#weight' => -10,
      );
      $form['clear_missing_sensors_wrapper']['info'] = array(
        '#type' => 'item',
        '#title' => t('Sensor message'),
        '#markup' => $result->getMessage(),
      );
      $form['clear_missing_sensors_wrapper']['clear_missing_sensor'] = array(
        '#type' => 'submit',
        '#submit' => array('monitoring_clear_missing_sensor_submit'),
        '#value' => t('Clear missing sensors'),
      );
    }

    return $form;
  }

  /**
   * Updates the available sensor list.
   *
   * @param array $available_sensors
   *   The available sensors list.
   * @param \Drupal\monitoring\Entity\SensorConfig[] $sensor_config
   *   The current sensor config.
   *
   * @return array
   *   Updated available sensors list.
   */
  protected function updateAvailableSensorsList($available_sensors, $sensor_config) {
    $new_sensors = array();

    foreach ($sensor_config as $key => $config) {
      // Check for newly added sensors. This is needed as some sensors get
      // enabled by default and not via monitoring_sensor_enable() callback that
      // takes care of updating the available sensors list.
      if (!isset($available_sensors[$key])) {
        $new_sensors[$key] = array(
          'name' => $key,
          'enabled' => $config->isEnabled(),
        );
      }
      // Check for sensor status changes that were not updated via
      // monitoring_sensor_enable/disable callbacks.
      elseif ($available_sensors[$key]['enabled'] != $config->isEnabled()) {
        $available_sensors[$key]['enabled'] = $config->isEnabled();
      }
    }

    // If we have new sensors add it to available list.
    if (!empty($new_sensors)) {
      \Drupal::state()->set('monitoring.available_sensors', $available_sensors + $new_sensors);
      \Drupal::logger('monitoring')->notice('@count new sensor/s added: @names',
        array('@count' => count($new_sensors), '@names' => implode(', ', array_keys($new_sensors))));
    }

    return $available_sensors;
  }

  /**
   * Checks for missing sensors.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   *   The current sensor result object.
   * @param array $available_sensors
   *   The available sensors list.
   * @param \Drupal\monitoring\Entity\SensorConfig[] $sensor_config
   *   The current sensor config.
   */
  protected function checkForMissingSensors(SensorResultInterface $result, $available_sensors, $sensor_config) {
    $result->setStatus(SensorResultInterface::STATUS_OK);

    $sensors_to_remove = array();
    // Check for missing sensors.
    foreach ($available_sensors as $available_sensor) {
      if (!in_array($available_sensor['name'], array_keys($sensor_config))) {
        // If sensor is missing and was not disabled prior to go missing do
        // escalate to critical status.
        if (!empty($available_sensor['enabled'])) {
          $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
          $result->addStatusMessage('Missing sensor @name', array('@name' => $available_sensor['name']));
        }
        // If sensor is missing but was disabled, add it to the remove list.
        else {
          $sensors_to_remove[] = $available_sensor['name'];
        }
      }
    }

    // If having sensor to remove, reset the available sensors variable.
    if (!empty($sensors_to_remove)) {
      foreach ($sensors_to_remove as $sensor_to_remove) {
        unset($available_sensors[$sensor_to_remove]);
      }
      \Drupal::state()->set('monitoring.available_sensors', $available_sensors);
      \Drupal::logger('monitoring')->notice('@count new sensor/s removed: @names',
        array('@count' => count($sensors_to_remove), '@names' => implode(', ', $sensors_to_remove)));
    }
  }
}
