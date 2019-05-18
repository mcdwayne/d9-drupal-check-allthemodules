<?php
/**
 * @file
 * Contains \Drupal\monitoring\SensorPlugin\SensorPluginInterface.
 */

namespace Drupal\monitoring\SensorPlugin;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface for a sensor plugin defining basic operations.
 *
 * @todo more
 */
interface SensorPluginInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Service setter.
   *
   * @param string $id
   *   Service name.
   * @param mixed $service
   *   The service to be used in the run method.
   */
  public function addService($id, $service);

  /**
   * Default configuration for a sensor.
   *
   * @return array
   *   An array of default configurations.
   */
  public function getDefaultConfiguration();

  /**
   * Configurable value type.
   *
   * @return bool
   *   If the value type is configurable or not.
   */
  public function getConfigurableValueType();

  /**
   * Gets service.
   *
   * @param string $id
   *   Service name.
   *
   * @return mixed
   */
  public function getService($id);

  /**
   * Gets sensor name (not the label).
   *
   * @return string
   *   Sensor name.
   */
  public function getSensorId();

  /**
   * Runs the sensor, updating $sensor_result.
   *
   * An implementation must provide any checks necessary to be able to populate
   * the provides sensor result instance with a combination of the following
   * possibilities:
   *
   *  - Set the sensor status to critical, warning, ok, info or unknown with
   *    SensorResultInterface::setStatus(). Defaults to unknown.
   *  - Set the sensor value with SensorResultInterface::setValue(). This can
   *    be a number or a string. Note that value_type defaults to numeric.
   *    If a sensor does not return a numeric result, it must be defined
   *    accordingly.
   *  - Set the expected sensor value with SensorResultInterface::setExpectedValue().
   *    When doing so, it is not necessary to set the sensor status explicitly,
   *    as that will happen implicitly. See below.
   *  - Set the sensor message with SensorResultInterface::setMessage(), which
   *    will then be used as is. The message must include all relevant
   *    information.
   *  - Add any number of status messages which will then be added to the
   *    final sensor message.
   *
   * Based on the provided information, the sensor result will then be compiled.
   * It will attempt to set the sensor status if not already
   * done explicitly by the sensor and will build a default message, unless a
   * message was already set with SensorResultInterface::setMessage().
   *
   * Sensors with unknown status can either be set based on an expected value or
   * thresholds. If the value does not match the expected value, the status
   * is set to critical. All numeric sensors have support for thresholds.
   *
   * The default sensor message will include information about the sensor value,
   * expected value, thresholds, the configured time interval and additional
   * status messages defined.
   * Provided value labels and value types will be considered for displaying the
   * sensor value. If neither value nor status messages are provided, the
   * message will default to "No value".
   *
   * Compiled message examples:
   *  - $90.00 in 1 day, expected $100.00.
   *    This is the message for a sensor with a commerce_currency value type, a
   *    configured time interval of one day and a value of 90 and expected value
   *    of 100.
   *  - 53 login attempts in 6 hours, exceeds 20, 10 for user administrator.
   *    This the message for a failed login sensor with value 53 with a
   *    threshold configuration of exceeds 20 and a status message "10 for user
   *    administrator".
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $sensor_result
   *   Sensor result object.
   *
   * @throws \Exception
   *   Can throw any exception. Must be caught and handled by the caller.
   *
   * @see \Drupal\monitoring\Result\SensorResultInterface::setValue()
   * @see \Drupal\monitoring\Result\SensorResultInterface::setExpectedValue()
   * @see \Drupal\monitoring\Result\SensorResultInterface::compile()
   *
   * @see \Drupal\monitoring\Result\SensorResultInterface::setMessage()
   * @see \Drupal\monitoring\Result\SensorResultInterface::addStatusMessage()
   */
  public function runSensor(SensorResultInterface $sensor_result);

  /**
   * Determines if sensor is enabled.
   *
   * @return bool
   *   Enabled flag.
   */
  public function isEnabled();

  /**
   * Calculates dependencies for the configured plugin.
   *
   * Dependencies are saved in the plugin's configuration entity and are used to
   * determine configuration synchronization order. For example, if the plugin
   * integrates with specific user roles, this method should return an array of
   * dependencies listing the specified roles.
   *
   * @return array
   *   An array of dependencies grouped by type (module, theme, entity). For
   *   example:
   * @code
   *   array(
   *     'entity' => array('user.role.anonymous', 'user.role.authenticated'),
   *     'module' => array('node', 'user'),
   *     'theme' => array('seven'),
   *   );
   * @endcode
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::getConfigDependencyName()
   */
  public function calculateDependencies();

  /**
   * Creates an instance of the sensor with config.
   *
   * Similar to ContainerFactoryPluginInterface but with typed config.
   * @see \Drupal\Core\Plugin\ContainerFactoryPluginInterface
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
   *   The configuration containing information about the sensor instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of the sensor.
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition);

}
