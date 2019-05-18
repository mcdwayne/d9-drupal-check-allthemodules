<?php
/**
 * @file
 * Contains \Drupal\monitoring\SensorPlugin\DatabaseAggregatorSensorPluginBase.
 */

namespace Drupal\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for database aggregator sensors.
 *
 * Defines sensor settings:
 * - conditions: A list of conditions to apply to the query.
 *   - field: Name of the field to filter on. Configurable fields are supported
 *     using the field_name.column_name syntax.
 *   - value: The value to limit by, either an array or a scalar value.
 *   - operator: Any of the supported operators.
 * - time_interval_field: Timestamp field name
 * - time_interval_value: Number of seconds defining the period
 *
 * Adds time interval to sensor settings form.
 */
abstract class DatabaseAggregatorSensorPluginBase extends SensorPluginBase {

  /**
   * Allows plugins to control if a timestamp field can be configured.
   *
   * @var bool
   */
  protected $configurableTimestampField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $configurableValueType = FALSE;

  /**
   * Gets conditions to be used in the select query.
   *
   * @return array
   *   List of conditions where each condition is an associative array:
   *   - field: Name of the field to filter on. Configurable fields are
   *     supported using the field_name.column_name syntax.
   *   - value: The value to limit by, either an array or a scalar value.
   *   - operator: Any of the supported operators.
   */
  protected function getConditions() {
    return $this->sensorConfig->getSetting('conditions', array());
  }

  /**
   * Gets the time field.
   *
   * @return string
   *   Time interval field.
   */
  protected function getTimeIntervalField() {
    return $this->sensorConfig->getSetting('time_interval_field');
  }

  /**
   * Gets the time interval value.
   *
   * @return int
   *   Time interval value.
   */
  protected function getTimeIntervalValue() {
    return $this->sensorConfig->getTimeIntervalValue();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['aggregation'] = array(
      '#type' => 'fieldset',
      '#title' => 'Time Aggregation',
      // Give the aggregation settings a high weight, so that they show up
      // after other configuration by default.
      '#weight' => 50,
    );


    $form['aggregation']['time_interval_field'] = array(
      '#type' => 'textfield',
      '#title' => t('Timestamp field'),
      '#default_value' => $this->sensorConfig->getSetting('time_interval_field'),
      '#access' => $this->configurableTimestampField,
    );

    $form['aggregation']['time_interval_value'] = array(
      '#type' => 'select',
      '#title' => t('Interval'),
      '#options' => $this->getTimeIntervalOptions(),
      '#description' => t('Select the time interval for which the results will be aggregated.'),
      '#default_value' => $this->getTimeIntervalValue(),
    );

    // Always show the interval value if a timestamp field is forced, otherwise
    // add states so it is only visible if something is entered.
    if ($this->configurableTimestampField) {
      $form['aggregation']['time_interval_value']['#states'] = array(
        'invisible' => array(
          ':input[name="settings[aggregation][time_interval_field]"]' => array('value' => ""),
        ),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    /** @var \Drupal\monitoring\Form\SensorForm $sensor_form */
    $sensor_form = $form_state->getFormObject();
    /** @var \Drupal\monitoring\SensorConfigInterface $sensor_config */
    $sensor_config = $sensor_form->getEntity();

    // Copy time interval field & value into settings if the field is specified.
    if ($interval_field = $form_state->getValue(array(
      'settings', 'aggregation', 'time_interval_field'))) {
      $sensor_config->settings['time_interval_field'] = $interval_field;
      $interval_value = $form_state->getValue(array(
        'settings', 'aggregation', 'time_interval_value'));

      $sensor_config->settings['time_interval_value'] = $interval_value;
      // Remove UI structure originated settings leftover.
    }
    else {
      // For consistency, empty the field + value setting if no field defined.
      unset($sensor_config->settings['time_interval_field']);
      unset($sensor_config->settings['time_interval_value']);
    }
    unset($sensor_config->settings['aggregation']);
  }

  /**
   * Returns time interval options.
   *
   * @return array
   *   Array with time interval options, keyed by time interval in seconds.
   */
  protected function getTimeIntervalOptions() {
    $time_intervals = array(
      600,
      900,
      1800,
      3600,
      7200,
      10800,
      21600,
      32400,
      43200,
      64800,
      86400,
      172800,
      259200,
      604800,
      1209600,
      2419200,
    );
    $date_formatter = \Drupal::service('date.formatter');
    return array_map(array($date_formatter, 'formatInterval'), array_combine($time_intervals, $time_intervals)) + array(0 => t('No restriction'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = array(
      'value_type' => 'number',
    );
    return $default_config;
  }

}
