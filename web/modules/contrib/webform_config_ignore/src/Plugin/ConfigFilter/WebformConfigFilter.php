<?php

namespace Drupal\webform_config_ignore\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ignore filter that reads partly from the active storage.
 *
 * @ConfigFilter(
 *   id = "config_webform_ignore",
 *   label = "Ignore webforms config",
 *   weight = 100
 * )
 */
class WebformConfigFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * Whether the plugin is disabled via settings.php.
   *
   * @var bool
   */
  protected $disabled;

  /**
   * Constructs a new Filter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active configuration store with the configuration on the site.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StorageInterface $active) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->active = $active;
    $this->disabled = Settings::get('webform_config_ignore_disabled', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage')
    );
  }

  /**
   * Match a config entity name against the webform config entities.
   *
   * @param string $config_name
   *   The name of the config entity to match against all ignored entities.
   *
   * @return bool
   *   True, if the config entity is to be ignored, false otherwise.
   */
  protected function matchConfigName($config_name) {

    if (strpos($config_name, 'webform.webform.') === 0) {
      return TRUE;
    }

    if (strpos($config_name, 'webform.webform_options.') === 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Read from the active configuration.
   *
   * This method will read the configuration from the active config store.
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
    $active_data = $this->active->read($name);

    // If the active webform data cannot be found, return data to allow
    // imports of new webforms.
    if ($active_data) {
      return $active_data;
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
      if (!array_key_exists($name, $data)) {
        $data[$name] = [];
      }
      $filtered_data[$name] = $this->activeRead($name, $data[$name]);
    }

    return $filtered_data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {

    if ($this->disabled) {
      return $data;
    }

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
    if ($this->disabled) {
      return $exists;
    }
    // A name exists if it is ignored and exists in the active storage.
    return $exists || ($this->matchConfigName($name) && $this->active->exists($name));
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    if ($this->disabled) {
      return $data;
    }
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
    if ($this->disabled) {
      return $data;
    }
    $active_names = $this->active->listAll($prefix);

    // Filter out only webform config names.
    $active_names = array_filter($active_names, [$this, 'matchConfigName']);

    // Return the data with the active names which are ignored merged in.
    return array_unique(array_merge($data, $active_names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->active->createCollection($collection));
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    // Add active collection names as there could be ignored config in them.
    return array_merge($collections, $this->active->getAllCollectionNames());
  }

}
