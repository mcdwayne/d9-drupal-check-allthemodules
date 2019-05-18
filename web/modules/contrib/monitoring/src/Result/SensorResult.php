<?php

namespace Drupal\monitoring\Result;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\monitoring\Entity\SensorResultDataInterface;
use Drupal\monitoring\Sensor\SensorCompilationException;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Sensor\Thresholds;

/**
 * Generic container for the sensor result.
 *
 * @todo more
 *
 * @see \Drupal\monitoring\Entity\SensorConfig
 * @see \Drupal\monitoring\SensorRunner
 */
class SensorResult implements SensorResultInterface {

  /**
   * The sensor config instance.
   *
   * @var \Drupal\monitoring\Entity\SensorConfig
   */
  protected $sensorConfig;

  /**
   * If the current result was constructed from a cache.
   *
   * @var bool
   */
  protected $isCached = FALSE;

  /**
   * The sensor result data.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Additional status messages from addStatusMessage().
   *
   * @var string[]
   */
  protected $statusMessages = array();

  /**
   * The main sensor message from setMessage().
   *
   * @var string[]
   */
  protected $sensorMessage = array();

  /**
   * The verbose output of the sensor execution.
   *
   * @var string
   */
  protected $verboseOutput;

  /**
   * The previous sensor result.
   *
   * @var \Drupal\monitoring\Entity\SensorResultDataInterface|null
   */
  protected $previousResult = NULL;

  /**
   * Instantiates a sensor result object.
   *
   * By default, the sensor status is STATUS_UNKNOWN with empty message.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
   *   Sensor config object.
   * @param array $cached_data
   *   Result data obtained from a cache.
   */
  function __construct(SensorConfig $sensor_config, array $cached_data = array()) {
    $this->sensorConfig = $sensor_config;
    if ($cached_data) {
      $this->data = $cached_data;
      $this->isCached = TRUE;
    }

    // Merge in defaults in case there is nothing cached for given sensor yet.
    $this->data += array(
      'sensor_status' => SensorResultInterface::STATUS_UNKNOWN,
      'sensor_message' => NULL,
      'sensor_expected_value' => NULL,
      'sensor_value' => NULL,
      'execution_time' => 0,
      'timestamp' => REQUEST_TIME,
    );
  }

  /**
   * Sets result data.
   *
   * @param string $key
   *   Data key.
   * @param mixed $value
   *   Data to set.
   */
  protected function setResultData($key, $value) {
    $this->data[$key] = $value;
    $this->isCached = FALSE;
  }

  /**
   * Gets result data.
   *
   * @param string $key
   *   Data key.
   *
   * @return mixed
   *   Stored data.
   */
  protected function getResultData($key) {
    return $this->data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->getResultData('sensor_status');
  }

  /**
   * {@inheritdoc}
   */
  public static function getStatusLabels() {
    return [
      self::STATUS_CRITICAL => t('Critical'),
      self::STATUS_WARNING => t('Warning'),
      self::STATUS_INFO => t('Info'),
      self::STATUS_OK => t('OK'),
      self::STATUS_UNKNOWN => t('Unknown'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel() {
    $labels = self::getStatusLabels();
    return $labels[$this->getResultData('sensor_status')];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->getResultData('sensor_message');
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message, array $variables = array()) {
    $this->sensorMessage = array(
      'message' => $message,
      'variables' => $variables,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addStatusMessage($message, array $variables = array()) {
    $this->statusMessages[] = array(
      'message' => $message,
      'variables' => $variables,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function compile() {
    // If the status is unknown we do the value assessment through
    // configurable thresholds.
    $threshold_message = NULL;
    if ($this->isUnknown()) {
      if ($this->getSensorConfig()->isDefiningThresholds()) {
        $threshold_message = $this->assessThresholds();
      }
      // If there are no thresholds, look for an expected value and compare it.
      // A sensor can not have both, as an expected value implies and exact
      // match and then thresholds are not needed.
      // @todo Check expected value first?
      elseif ($this->getExpectedValue() !== NULL) {
        $this->assessComparison();
      }
      elseif ($this->getSensorConfig()->isNumeric()) {
        // Numeric sensors that do not have thresholds or an expected value
        // default to OK.
        $this->setStatus(SensorResultInterface::STATUS_OK);
      }
    }

    if ($this->getSensorConfig()->getValueType() == 'bool') {
      $msg_expected = $this->getExpectedValue() ? 'TRUE' : 'FALSE';
    }
    else {
      $msg_expected = $this->getExpectedValue();
    }

    if (!empty($this->sensorMessage)) {
      // A message has been set by the sensor, use that as is and only do
      // placeholder replacements with the provided variables.
      $message = new FormattableMarkup($this->sensorMessage['message'], $this->sensorMessage['variables']);
    }
    else {

      // No message has been provided, attempt to build one.

      // Set the default message variables.
      $default_variables = array(
        '@sensor' => $this->getSensorId(),
        '@formatted_value' => $this->getFormattedValue($this->getValue()),
        '@time' => $this->getTimestamp(),
        '@expected' => $msg_expected,
        '@time_interval' => \Drupal::service('date.formatter')->formatInterval($this->getSensorConfig()->getTimeIntervalValue()),
      );

      // Build an array of message parts.
      $messages = array();

      // Add the sensor value if provided.
      if ($this->getValue() !== NULL) {

        // If the sensor defines time interval value we append
        // the info to the message.
        if ($this->getSensorConfig()->getTimeIntervalValue()) {
          $messages[] = new FormattableMarkup('@formatted_value in @time_interval', $default_variables);
        }
        else {
          $messages[] = $default_variables['@formatted_value'];
        }
      }
      // Avoid an empty sensor message.
      elseif (empty($this->statusMessages)) {
        $messages[] = 'No value';
      }

      // Set the expected value message if the sensor did not match.
      if ($this->isCritical() && $this->getExpectedValue() !== NULL) {
        $messages[] = new FormattableMarkup('expected @expected', $default_variables);
      }
      // Set the threshold message if there is any.
      if ($threshold_message !== NULL) {
        $messages[] = $threshold_message;
      }

      $renderer = \Drupal::service('renderer');

      // Append all status messages which were added by the sensor.
      foreach ($this->statusMessages as $msg) {
        if (is_array($msg['message'])) {
          $messages[] = new FormattableMarkup($renderer->renderPlain($msg['message']), array_merge($default_variables, $msg['variables']));
        }
        else {
          $messages[] = new FormattableMarkup($msg['message'], array_merge($default_variables, $msg['variables']));
        }
      }

      $message = strip_tags(implode(', ', $messages));
    }

    $this->setResultData('sensor_message', $message);
  }

  /**
   * Performs comparison of expected and actual sensor values.
   */
  protected function assessComparison() {
    if ($this->getValue() != $this->getExpectedValue()) {
      $this->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    else {
      $this->setStatus(SensorResultInterface::STATUS_OK);
    }
  }

  /**
   * Deal with thresholds.
   *
   * Set the sensor value  based on threshold configuration.
   *
   * @return string
   *   The message associated with the threshold.
   *
   * @see \Drupal\monitoring\Sensor\Thresholds
   */
  protected function assessThresholds() {
    $thresholds = new Thresholds($this->sensorConfig);
    $matched_threshold = $thresholds->getMatchedThreshold($this->getValue());

    // Set sensor status based on matched threshold.
    $this->setStatus($matched_threshold);
    // @todo why not just set the status message?
    return $thresholds->getStatusMessage();
  }

  /**
   * Formats the value to be human readable.
   *
   * @param mixed $value
   *   Sensor result value.
   *
   * @return string
   *   Formatted value.
   *
   * @throws \Drupal\monitoring\Sensor\SensorCompilationException
   */
  public function getFormattedValue($value) {

    $value_type = $this->getSensorConfig()->getValueType();
    // If the value type is defined we have the formatter that will format the
    // value to be ready for display.

    if (!empty($value_type)) {

      $value_types = monitoring_value_types();
      if (!isset($value_types[$value_type])) {
        throw new SensorCompilationException(new FormattableMarkup('Invalid value type @type', array('@type' => $value_type)));
      }
      elseif (empty($value_types[$value_type]['formatter_callback']) && $label = $this->getSensorConfig()->getValueLabel()) {
        $label = Unicode::strtolower($label);
        return new FormattableMarkup('@value @label', array('@value' => $value, '@label' => $label));
      }
      elseif (isset($value_types[$value_type]['formatter_callback']) && !function_exists($value_types[$value_type]['formatter_callback'])) {
        throw new SensorCompilationException(new FormattableMarkup('Formatter callback @callback for @type does not exist',
          array('@callback' => $value_types[$value_type]['formatter_callback'], '@type' => $value_type)));
      }
      elseif(isset($value_types[$value_type]['formatter_callback'])) {
        $callback = $value_types[$value_type]['formatter_callback'];
        return $callback($this);
      }
    }

    // If there is no value formatter we try to provide something human readable
    // by concatenating the value and label.

    if ($label = $this->getSensorConfig()->getValueLabel()) {
      // @todo This assumption will no longer work when non-english messages
      // supported.
      $label = Unicode::strtolower($label);
      return new FormattableMarkup('@value @label', array('@value' => $value, '@label' => $label));
    }

    return new FormattableMarkup('Value @value', array('@value' => $value));
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->getSensorConfig()->isBool()) {
      return (bool) $this->getResultData('sensor_value');
    }
    return $this->getResultData('sensor_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getExpectedValue() {
    if ($this->getSensorConfig()->isBool()) {
      return (bool) $this->getResultData('sensor_expected_value');
    }
    return $this->getResultData('sensor_expected_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionTime() {
    return round($this->getResultData('execution_time'), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($sensor_status) {
    $this->setResultData('sensor_status', $sensor_status);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($sensor_value) {
    $this->setResultData('sensor_value', $sensor_value);
  }

  /**
   * {@inheritdoc}
   */
  public function setExpectedValue($sensor_value) {
    $this->setResultData('sensor_expected_value', $sensor_value);
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutionTime($execution_time) {
    $this->setResultData('execution_time', $execution_time);
  }

  /**
   * {@inheritdoc}
   */
  public function toNumber() {
    $sensor_value = $this->getValue();

    if (is_numeric($sensor_value)) {
      return $sensor_value;
    }

    // Casting to int should be good enough as boolean will get casted to 0/1
    // and string as well.
    return (int) $sensor_value;
  }

  /**
   * {@inheritdoc}
   */
  public function isWarning() {
    return $this->getStatus() == SensorResultInterface::STATUS_WARNING;
  }

  /**
   * {@inheritdoc}
   */
  public function isCritical() {
    return $this->getStatus() == SensorResultInterface::STATUS_CRITICAL;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnknown() {
    return $this->getStatus() == SensorResultInterface::STATUS_UNKNOWN;
  }

  /**
   * {@inheritdoc}
   */
  public function isOk() {
    return $this->getStatus() == SensorResultInterface::STATUS_OK;
  }

  /**
   * Returns sensor result data as array.
   *
   * @return array
   *   An array with data having following keys:
   *   - sensor_name
   *   - value
   *   - expected_value
   *   - numeric_value
   *   - status
   *   - message
   *   - execution_time
   *   - timestamp
   */
  public function toArray() {
    return array(
      'sensor_name' => $this->getSensorId(),
      'value' => $this->getValue(),
      'expected_value' => $this->getExpectedValue(),
      'numeric_value' => $this->toNumber(),
      'status' => $this->getStatus(),
      'message' => $this->getMessage(),
      'execution_time' => $this->getExecutionTime(),
      'timestamp' => $this->getTimestamp(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isCached() {
    return $this->isCached;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->getResultData('timestamp');
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorId() {
    return $this->sensorConfig->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorConfig() {
    return $this->sensorConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function setVerboseOutput($verbose_output) {
    $this->verboseOutput = $verbose_output;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerboseOutput() {
    return $this->verboseOutput;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviousResult(SensorResultDataInterface $previous_result = NULL) {
    $this->previousResult = $previous_result;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousResult() {
    return $this->previousResult;
  }
}
