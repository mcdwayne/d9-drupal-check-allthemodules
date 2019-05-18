<?php

declare(strict_types = 1);

namespace Drupal\config_owner;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Service that can validate the dependencies of a configuration.
 */
class ConfigDependencyValidator {

  /**
   * The active storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ConfigDependencyValidator constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(StorageInterface $active_storage, ConfigFactoryInterface $configFactory) {
    $this->activeStorage = $active_storage;
    $this->configFactory = $configFactory;
  }

  /**
   * Validates an array of config data that contains dependency information.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return bool
   *   TRUE if all dependencies are present, FALSE otherwise.
   */
  public function validateDependencies(string $config_name, array $data, array $all_config = []) {
    $enabled_extensions = $this->getEnabledExtensions();

    if (!isset($data['dependencies'])) {
      // Simple config or a config entity without dependencies.
      list($provider) = explode('.', $config_name, 2);

      return in_array($provider, $enabled_extensions, TRUE);
    }

    if (empty($all_config)) {
      $all_config = $this->activeStorage->listAll();
    }

    $missing = $this->getMissingDependencies($config_name, $data, $enabled_extensions, $all_config);

    return empty($missing);
  }

  /**
   * Returns an array of missing dependencies for a config object.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return array
   *   A list of missing config dependencies.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function getMissingDependencies(string $config_name, array $data, array $enabled_extensions, array $all_config) {
    if (!isset($data['dependencies'])) {
      return [];
    }

    list($provider) = explode('.', $config_name, 2);
    $all_dependencies = $data['dependencies'];

    // Ensure enforced dependencies are included.
    if (isset($all_dependencies['enforced'])) {
      $all_dependencies = array_merge($all_dependencies, $data['dependencies']['enforced']);
      unset($all_dependencies['enforced']);
    }
    // Ensure the configuration entity type provider is in the list of
    // dependencies.
    if (!isset($all_dependencies['module']) || !in_array($provider, $all_dependencies['module'])) {
      $all_dependencies['module'][] = $provider;
    }

    $missing = [];
    foreach ($all_dependencies as $type => $dependencies) {
      $list_to_check = [];
      switch ($type) {
        case 'module':
        case 'theme':
          $list_to_check = $enabled_extensions;
          break;

        case 'config':
          $list_to_check = $all_config;
          break;
      }

      if (!empty($list_to_check)) {
        $missing = array_merge($missing, array_diff($dependencies, $list_to_check));
      }
    }

    return $missing;
  }

  /**
   * Gets the list of enabled extensions including both modules and themes.
   *
   * @return array
   *   A list of enabled extensions which includes both modules and themes.
   */
  protected function getEnabledExtensions() {
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = (array) $extension_config->get('module');
    $enabled_extensions += (array) $extension_config->get('theme');

    // Core can provide configuration.
    $enabled_extensions['core'] = 'core';

    return array_keys($enabled_extensions);
  }

}
