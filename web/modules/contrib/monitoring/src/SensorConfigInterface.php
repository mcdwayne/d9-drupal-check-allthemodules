<?php
/**
 * @file
 * Contains \Drupal\monitoring\SensorConfigInterface.
 */

namespace Drupal\monitoring;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

interface SensorConfigInterface extends ConfigEntityInterface {
  /**
   * Gets sensor label.
   *
   * The sensor label might not be self-explaining enough or unique without
   * the category, the category should always be present when the label is
   * displayed.
   *
   * @return string
   *   Sensor label.
   */
  public function getLabel();

  /**
   * Gets sensor description.
   *
   * @return string
   *   Sensor description.
   */
  public function getDescription();

  /**
   * Gets sensor plugin class.
   *
   * @return string
   *   SensorPlugin class
   */
  public function getSensorClass();

  /**
   * Gets the sensor plugin.
   *
   * @return \Drupal\monitoring\SensorPlugin\SensorPluginInterface
   *   Instantiated sensor.
   */
  public function getPlugin();

  /**
   * Gets sensor categories.
   *
   * @return string
   *   Categories.
   */
  public function getCategory();

  /**
   * Gets sensor value label.
   *
   * In case the sensor defined a value_label, it will use it as label.
   *
   * Next if the sensor defines a value_type, it will use the label provided for
   * that type by monitoring_value_types().
   *
   * If nothing is defined, it returns NULL.
   *
   * @return string|null
   *   Sensor value label.
   */
  public function getValueLabel();

  /**
   * Gets sensor value type.
   *
   * @return string|null
   *   Sensor value type.
   *
   * @see \monitoring_value_types().
   */
  public function getValueType();

  /**
   * Determines if the sensor value is numeric.
   *
   * @return bool
   *   TRUE if the sensor value is numeric.
   */
  public function isNumeric();

  /**
   * Determines if the sensor value type is boolean.
   *
   * @return bool
   *   TRUE if the sensor value type is boolean.
   */
  public function isBool();

  /**
   * Gets sensor caching time.
   *
   * @return int
   *   Caching time in seconds.
   */
  public function getCachingTime();

  /**
   * Gets configured threshold type.
   *
   * Defaults to none.
   *
   * @return string|null
   *   Threshold type.
   */
  public function getThresholdsType();

  /**
   * Gets the configured threshold value.
   *
   * @param string $key
   *   Name of the threshold, for example warning or critical.
   *
   * @return int|null
   *   The threshold value or NULL if not configured.
   */
  public function getThresholdValue($key);

  /**
   * Gets all settings.
   *
   * @return array
   *   Settings as an array.
   */
  public function getSettings();

  /**
   * Gets thresholds.
   *
   * @return array
   *   Thresholds as an array.
   */
  public function getThresholds();

  /**
   * Gets the time interval value.
   *
   * @return int
   *   Number of seconds of the time interval.
   *   NULL in case the sensor does not define the time interval.
   */
  public function getTimeIntervalValue();

  /**
   * Gets the setting of a key.
   *
   * @param string $key
   *   Setting key.
   * @param mixed $default
   *   Default value if the setting does not exist.
   *
   * @return mixed
   *   Setting value.
   */
  public function getSetting($key, $default = NULL);

  /**
   * Checks if sensor is enabled.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Checks if sensor provides extended info.
   *
   * @return bool
   */
  public function isExtendedInfo();

  /**
   * Checks if sensor defines thresholds.
   *
   * @return bool
   */
  public function isDefiningThresholds();

  /**
   * Compiles sensor values to an associative array.
   *
   * @return array
   *   Sensor config associative array.
   */
  public function getDefinition();

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b);

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies();

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE);
}
