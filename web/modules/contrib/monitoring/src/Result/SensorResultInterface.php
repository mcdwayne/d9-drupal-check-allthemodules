<?php
/**
 * @file
 * Contains \Drupal\monitoring\Result\SensorResultInterface.
 */

namespace Drupal\monitoring\Result;

use Drupal\monitoring\Entity\SensorResultDataInterface;

/**
 * Interface for a sensor result.
 *
 * @todo more
 */
interface SensorResultInterface extends SensorResultDataInterface {

  /**
   * Adds sensor status message.
   *
   * Multiple status messages can be added to a single result and will be added
   * to the final status message.
   *
   * @param string $message
   *   Message to be set.
   * @param array $variables
   *   Dynamic values to be replaced for placeholders in the message.
   *
   * @see \Drupal\monitoring\Result\SensorResultInterface::setMessage()
   */
  public function addStatusMessage($message, array $variables = array());

  /**
   * Compiles added status messages sets the status.
   *
   * If the status is STATUS_UNKNOWN, this will attempt to set the status
   * based on expected value and threshold configurations. See
   * \Drupal\monitoring\SensorPlugin\SensorPluginInterface::runSensor() for details.
   *
   * @throws \Drupal\monitoring\Sensor\SensorCompilationException
   *   Thrown if an error occurs during the sensor result compilation.
   */
  public function compile();

  /**
   * Gets the sensor metric value formatted for UI output.
   *
   * @param mixed $value
   *   Sensor result value.
   *
   * @return mixed
   *   Whatever value the sensor is supposed to return.
   */
  public function getFormattedValue($value);

  /**
   * Gets the sensor expected value.
   *
   * @return mixed
   *   Whatever value the sensor is supposed to return.
   */
  public function getExpectedValue();

  /**
   * Sets sensor status.
   *
   * @param string $status
   *   One of SensorResultInterface::STATUS_* constants.
   */
  public function setStatus($status);

  /**
   * Sets sensor value.
   *
   * @param mixed $value
   */
  public function setValue($value);

  /**
   * Sets the final result message.
   *
   * If this is set, then the compilation will not extend the message in any
   * way and the sensor completely responsible for making sure that all
   * relevant information like the sensor value is part of the message.
   *
   * @param string $message
   *   Message to be set.
   * @param array $variables
   *   Dynamic values to be replaced for placeholders in the message.
   */
  public function setMessage($message, array $variables = array());

  /**
   * Sets sensor execution time in ms.
   *
   * @param float $time
   *   Sensor execution time in ms.
   */
  public function setExecutionTime($time);

  /**
   * Sets sensor expected value.
   *
   * Set to NULL if you want to prevent the default sensor result assessment.
   * Use 0/FALSE values instead.
   *
   * In case an interval is expected, do not set the expected value, thresholds
   * are used instead.
   *
   * The expected value is not considered when thresholds are configured.
   *
   * @param mixed $value
   */
  public function setExpectedValue($value);

  /**
   * Casts/processes the sensor value into numeric representation.
   *
   * @return number
   *   Numeric sensor value.
   */
  public function toNumber();

  /**
   * Determines if data for given result object are cached.
   *
   * @return boolean
   *   Cached flag.
   */
  public function isCached();

  /**
   * Gets sensor result data as array.
   *
   * @return array
   *   Sensor result data as array.
   */
  public function toArray();

  /**
   * Gets sensor name.
   *
   * @return string
   */
  public function getSensorId();

  /**
   * Gets sensor config.
   *
   * @return \Drupal\monitoring\Entity\SensorConfig
   */
  public function getSensorConfig();

  /**
   * Checks if sensor is in UNKNOWN state.
   *
   * @return boolean
   */
  public function isUnknown();

  /**
   * Checks if sensor is in WARNING state.
   *
   * @return boolean
   */
  public function isWarning();

  /**
   * Checks if sensor is in CRITICAL state.
   *
   * @return boolean
   */
  public function isCritical();

  /**
   * Checks if sensor is in OK state.
   *
   * @return boolean
   */
  public function isOk();

  /**
   * Set the verbose output.
   *
   * @param array $verbose_output
   *   The verbose output as a render array.
   */
  public function setVerboseOutput($verbose_output);

  /**
   * Returns the verbose output.
   *
   * Verbose output is not persisted and is only available if the sensor result
   * is not cached.
   *
   * @return array
   *   The verbose output as a render array.
   */
  public function getVerboseOutput();

  /**
   * Sets the previous sensor result.
   *
   * @param \Drupal\monitoring\Entity\SensorResultDataInterface|null $previous_result
   *   A SensorResultEntity representing the previous sensor result to set.
   *   NULL if there is no previous result.
   */
  public function setPreviousResult(SensorResultDataInterface $previous_result = NULL);

  /**
   * Gets the previous sensor result.
   *
   * @return \Drupal\monitoring\Entity\SensorResultDataInterface|null
   *   A SensorResultEntity representing the previous sensor result. NULL if
   *   there is no previous result.
   */
  public function getPreviousResult();

}
