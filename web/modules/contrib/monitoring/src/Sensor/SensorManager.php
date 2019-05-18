<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\SensorManager.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\monitoring\Entity\SensorConfig;

/**
 * Manages sensor definitions and settings.
 *
 * Provides list of enabled sensors.
 * Sensors can be listed by category.
 *
 * Maintains a (non persistent) info cache.
 * Enables and disables sensors.
 *
 */
class SensorManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * List of sensor definitions.
   *
   * @var \Drupal\monitoring\Entity\SensorConfig[]
   */
  protected $sensor_config;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a sensor manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/monitoring/SensorPlugin', $namespaces, $module_handler, '\Drupal\monitoring\SensorPlugin\SensorPluginInterface', 'Drupal\monitoring\Annotation\SensorPlugin');
    $this->alterInfo('monitoring_sensor_plugins');
    $this->setCacheBackend($cache_backend, 'monitoring_sensor_plugins');
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // Configuration contains SensorConfig object. Extracting
    // it to use for sensor object creation.
    $sensor_config = $configuration['sensor_config'];
    $definition = $this->getDefinition($plugin_id);
    // SensorPlugin class from the sensor definition.
    /** @var \Drupal\monitoring\SensorPlugin\SensorPluginInterface $class */
    $class = $definition['class'];
    // Creating instance of the sensor. Refer SensorPlugin.php for arguments.
    return $class::create(\Drupal::getContainer(), $sensor_config, $plugin_id, $definition);
  }

  /**
   * Returns monitoring sensor config.
   *
   * @return \Drupal\monitoring\Entity\SensorConfig[]
   *   List of SensorConfig instances.
   */
  public function getAllSensorConfig() {
    $sensors = SensorConfig::loadMultiple();

    // Sort the sensors by category and label.
    uasort($sensors, "\Drupal\monitoring\Entity\SensorConfig::sort");

    return $sensors;
  }

  /**
   * Returns monitoring sensor config for enabled sensors.
   *
   * @return \Drupal\monitoring\Entity\SensorConfig[]
   *   List of SensorConfig instances.
   */
  public function getEnabledSensorConfig() {
    $enabled_sensors = array();
    foreach ($this->getAllSensorConfig() as $sensor_config) {
      if ($sensor_config->isEnabled()) {
        $enabled_sensors[$sensor_config->id()] = $sensor_config;
      }
    }
    return $enabled_sensors;
  }

  /**
   * Returns monitoring sensor config for a given sensor.
   *
   * Directly use SensorConfig::load($name) if sensor existence assured.
   *
   * @param string $sensor_name
   *   Sensor id.
   *
   * @return \Drupal\monitoring\Entity\SensorConfig
   *   A single SensorConfig instance.
   *
   * @throws \Drupal\monitoring\Sensor\NonExistingSensorException
   *   Thrown if the requested sensor does not exist.
   */
  public function getSensorConfigByName($sensor_name) {
    $sensor_config = SensorConfig::load($sensor_name);
    if ($sensor_config == NULL) {
      throw new NonExistingSensorException(new FormattableMarkup('Sensor @sensor_name does not exist', array('@sensor_name' => $sensor_name)));
    }
    return $sensor_config;
  }

  /**
   * Gets sensor config grouped by categories.
   *
   * @todo: The enabled flag is strange, FALSE should return all?
   *
   * @param bool $enabled
   *   Sensor isEnabled flag.
   *
   * @return \Drupal\monitoring\Entity\SensorConfig[][]
   *   Sensor config.
   */
  public function getSensorConfigByCategories($enabled = TRUE) {
    $config_by_categories = array();
    foreach ($this->getAllSensorConfig() as $sensor_name => $sensor_config) {
      if ($sensor_config->isEnabled() != $enabled) {
        continue;
      }

      $config_by_categories[$sensor_config->getCategory()][$sensor_name] = $sensor_config;
    }

    return $config_by_categories;
  }

  /**
   * Reset the static cache.
   */
  public function resetCache() {
    $this->sensor_config = array();
  }

  /**
   * Enable a sensor.
   *
   * Checks if the sensor is enabled and enables it if not.
   *
   * @param string $sensor_name
   *   Sensor name to be enabled.
   *
   * @throws \Drupal\monitoring\Sensor\NonExistingSensorException
   *   Thrown if the requested sensor does not exist.
   */
  public function enableSensor($sensor_name) {
    $sensor_config = $this->getSensorConfigByName($sensor_name);
    if (!$sensor_config->isEnabled()) {
      $sensor_config->status = TRUE;
      $sensor_config->save();

      $available_sensors = \Drupal::state()->get('monitoring.available_sensors', array());

      if (!isset($available_sensors[$sensor_name])) {
        // Use the watchdog message as the disappeared sensor does when new
        // sensors are detected.
        \Drupal::logger('monitoring')->notice('@count new sensor/s added: @names',
          array('@count' => 1, '@names' => $sensor_name));
      }

      $available_sensors[$sensor_name]['enabled'] = TRUE;
      $available_sensors[$sensor_name]['name'] = $sensor_name;
      \Drupal::state()->set('monitoring.available_sensors', $available_sensors);
    }
  }

  /**
   * Disable a sensor.
   *
   * Checks if the sensor is enabled and if so it will disable it and remove
   * from the active sensor list.
   *
   * @param string $sensor_name
   *   Sensor name to be disabled.
   *
   * @throws \Drupal\monitoring\Sensor\NonExistingSensorException
   *   Thrown if the requested sensor does not exist.
   */
  public function disableSensor($sensor_name) {
    $sensor_config = $this->getSensorConfigByName($sensor_name);
    if ($sensor_config->isEnabled()) {
      $sensor_config->status = FALSE;
      $sensor_config->save();
      $available_sensors = \Drupal::state()->get('monitoring.available_sensors', array());
      $available_sensors[$sensor_name]['enabled'] = FALSE;
      $available_sensors[$sensor_name]['name'] = $sensor_name;
      \Drupal::state()->set('monitoring.available_sensors', $available_sensors);
    }
  }

  /**
   * Rebuild the sensor list.
   *
   * Automatically creates sensors based on new
   */
  public function rebuildSensors() {
    // Declaring a flag for updated sensors.
    $updated_sensors = FALSE;

    // Load .install files
    include DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();
    $storage = $this->entityTypeManager->getStorage('monitoring_sensor_config');

    $this->moduleHandler->resetImplementations();

    // Iterate through the installed implemented modules to see if
    // there are any new requirements hook updates and initialize them.
    foreach ($this->moduleHandler->getImplementations('requirements') as $module) {
      if(!$storage->load('core_requirements_' . $module)) {
        if (initialize_requirements_sensors($module)) {
          drupal_set_message($this->t('The sensor @sensor has been added.', ['@sensor' => $storage->load('core_requirements_' . $module)->label()]));
          $updated_sensors = TRUE;
        }
      }
    }

    // Delete any updated sensors that are not implemented in the requirements
    // hook anymore.
    $sensor_ids = $storage->getQuery()
      ->condition('plugin_id', 'core_requirements')
      ->execute();
    /** @var \Drupal\monitoring\SensorConfigInterface $sensor */
    foreach ($storage->loadMultiple($sensor_ids) as $sensor) {
      $module = $sensor->getSetting('module');
      if (!$this->moduleHandler->implementsHook($module, 'requirements')) {
        drupal_set_message($this->t('The sensor @sensor has been removed.', ['@sensor' => $sensor->label()]));
        $sensor->delete();
        $updated_sensors = TRUE;

        // Remove the sensor from the list of available sensors.
        $available_sensors = \Drupal::state()->get('monitoring.available_sensors', []);
        unset($available_sensors[$sensor->id()]);
        \Drupal::state()->set('monitoring.available_sensors', $available_sensors);
      }
    }

    /** @var \Drupal\Core\Config\StorageInterface[] $config_storages */
    $config_storages[] = new FileStorage($this->moduleHandler->getModule('monitoring')->getPath() . '/config/install');
    $config_storages[] = new FileStorage($this->moduleHandler->getModule('monitoring')->getPath() . '/config/optional');

    // Rebuilds all non-addable sensors.
    foreach ($this->getDefinitions() as $sensor_definition) {
      if (!$sensor_definition['addable']) {

        if ($sensor_definition['id'] !== 'update_status') {
          $config_ids = [$sensor_definition['id']];
        }
        else {
          $config_ids = ['update_core', 'update_contrib'];
        }

        foreach ($config_ids as $config_id) {
          // Checks if the sensor is not created.
          if (!$storage->load($config_id)) {
            // Check the two directories install and optional for sensors that need to be created.
            foreach ($config_storages as $config_storage) {
              if ($data = $config_storage->read('monitoring.sensor_config.' . $config_id)) {
                $storage->create($data)->trustData()->save();
                drupal_set_message($this->t('The sensor @sensor has been created.', ['@sensor' => (string) $sensor_definition['label']]));
                $updated_sensors = TRUE;
                break;
              }
            }
          }
        }
      }
    }

    // Set message to inform the user that there were no updated sensors.
    if ($updated_sensors == FALSE) {
      drupal_set_message($this->t('No changes were made.'));
    }
  }

  /**
   * Returns if an array is flat.
   *
   * @param array $array
   *   The array to check.
   *
   * @return bool
   *   TRUE if the array has no values that are arrays again.
   */
  protected function isFlatArray(array $array) {
    foreach ($array as $value) {
      if (is_array($value)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
