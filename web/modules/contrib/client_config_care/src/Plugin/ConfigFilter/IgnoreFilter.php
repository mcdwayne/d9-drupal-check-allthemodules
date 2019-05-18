<?php

namespace Drupal\client_config_care\Plugin\ConfigFilter;

use Drupal\client_config_care\Deactivator;
use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\client_config_care\LogMessageStorage;
use Drupal\Component\Utility\NestedArray;
use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ignore filter that reads partly from the active storage.
 *
 * @ConfigFilter(
 *   id = "client_config_care",
 *   label = "Config Config Care",
 *   weight = 100
 * )
 */
class IgnoreFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  const FORCE_EXCLUSION_PREFIX = '~';
  const INCLUDE_SUFFIX = '*';

  /**
   * @var StorageInterface
   */
  protected $activeStorage;

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var Deactivator
   */
  private $deactivator;

  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, StorageInterface $active, EntityTypeManagerInterface $entityTypeManager, Deactivator $deactivator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->activeStorage = $active;
    $this->entityTypeManager = $entityTypeManager;
    $this->deactivator = $deactivator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage'),
			$container->get('entity_type.manager'),
      $container->get('client_config_care.deactivator')
    );
  }

  protected function matchConfigName(string $configName): bool {
    if ($this->deactivator->isDeactivated()) {
      return FALSE;
    }

    /**
     * @var ConfigBlockerEntity $entityStorage
     */
    $entityStorage = $this->entityTypeManager->getStorage(ConfigBlockerEntity::ENTITY_ID);
    $configBlocker = $entityStorage->loadByProperties(['name' => $configName]);

    if (!empty($configBlocker)) {
      $message[$configName] = "Config with name $configName has not been imported, because there exists a blocker from the Client Config Care module.";
			LogMessageStorage::addMessage($message);

			return TRUE;
		}

    return FALSE;
  }

  /**
   * Read from the active configuration.
   *
   * This method will read the configuration from the active config store.
   * But rather than just straight up returning the value it will check if
   * a nested config key is set to be ignored and set only that value on the
   * data to be filtered.
   *
   * @param string $name
   *   The name of the configuration to read.
   * @param mixed $data
   *   The data to be filtered.
   *
   * @return mixed
   *   The data filtered or read from the active storage.
   */
  protected function activeRead($name, $data) {
    $keys = [];

    $entityStorage = $this->entityTypeManager->getStorage(ConfigBlockerEntity::ENTITY_ID);
    $configBlockers = $entityStorage->loadMultiple();

    foreach ($configBlockers as $configBlocker) {
      // Split the ignore settings so that we can ignore individual keys.
      $ignored = explode(':', $configBlocker->get('name')->getValue()['0']['value']);
      if (fnmatch($ignored[0], $name)) {
        if (count($ignored) == 1) {
          // If one of the definitions does not have keys ignore the
          // whole config.
          return $this->activeStorage->read($name);
        }
        else {
          // Add the sub parts to ignore to the keys.
          $keys[] = $ignored[1];
        }
      }

    }

    $active = $this->activeStorage->read($name);
    foreach ($keys as $key) {
      $parts = explode('.', $key);

      if (count($parts) == 1) {
        if (isset($active[$key])) {
          $data[$key] = $active[$key];
        }
      }
      else {
        $value = NestedArray::getValue($active, $parts, $key_exists);
        if ($key_exists) {
          // Enforce the value if it existed in the active config.
          NestedArray::setValue($data, $parts, $value, TRUE);
        }
      }
    }

    return $data;
  }

  /**
   * Read multiple from the active storage.
   *
   * @param array $names
   *   The names of the configuration to read.
   * @param array $data
   *   The data to filter.
   *
   * @return array
   *   The new data.
   */
  protected function activeReadMultiple(array $names, array $data) {
    $filtered_data = [];
    foreach ($names as $name) {
      $filtered_data[$name] = $this->activeRead($name, $data[$name]);
    }

    return $filtered_data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    // Read from the active storage when the name is in the ignored list.
    if ($this->matchConfigName($name)) {
      return $this->activeRead($name, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // A name exists if it is ignored and exists in the active storage.
    return $exists || ($this->matchConfigName($name) && $this->activeStorage->exists($name));
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    // Limit the names which are read from the active storage.
    $names = array_filter($names, [$this, 'matchConfigName']);
    $active_data = $this->activeReadMultiple($names, $data);

    // Return the data with merged in active data.
    return array_merge($data, $active_data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $active_names = $this->activeStorage->listAll($prefix);
    // Filter out only ignored config names.
    $active_names = array_filter($active_names, [$this, 'matchConfigName']);

    // Return the data with the active names which are ignored merged in.
    return array_unique(array_merge($data, $active_names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->activeStorage->createCollection($collection), $this->entityTypeManager, $this->deactivator);
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    // Add active collection names as there could be ignored config in them.
    return array_merge($collections, $this->activeStorage->getAllCollectionNames());
  }

}
