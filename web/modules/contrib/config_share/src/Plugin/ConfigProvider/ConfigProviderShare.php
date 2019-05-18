<?php

namespace Drupal\config_share\Plugin\ConfigProvider;

use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from a share directory.
 *
 * @ConfigProvider(
 *   id = \Drupal\config_share\Plugin\ConfigProvider\ConfigProviderShare::ID,
 *   weight = 10,
 *   label = @Translation("Share"),
 *   description = @Translation("Configuration to be installed only when required by other conifguration."),
 * )
 */
class ConfigProviderShare extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = 'config/shared';

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    $shared_config_to_create = $this->getSharedConfigToCreate($config_to_create);
    $config_to_create = array_merge($shared_config_to_create, $config_to_create);
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {
    $shared_config_to_add = $this->getSharedConfigToCreate($this->providerStorage->listAll(), $extensions);
    foreach ($shared_config_to_add as $name => $data) {
      $this->providerStorage->write($name, $data);
    }
  }

  /**
   * Merges in shared configuration.
   */
  protected function getSharedConfigToCreate(array $config_to_create, array $extensions = []) {
    // Get data for the default collection.
    $shared_config_to_create = $this->getCollectionSharedConfigToCreate($config_to_create, $extensions);

    // Get all data from the remaining collections.
    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    foreach ($collection_info->getCollectionNames() as $collection) {
      $collection_shared_config_to_create = $this->getCollectionSharedConfigToCreate(array_merge($config_to_create, $shared_config_to_create), $extensions, $collection);
      $shared_config_to_create = array_merge($shared_config_to_create, $collection_shared_config_to_create);
    }

    return $shared_config_to_create;
  }

  /**
   * Merges in shared configuration for a given collection.
   */
  protected function getCollectionSharedConfigToCreate(array $config_to_create, array $extensions = [], $collection = StorageInterface::DEFAULT_COLLECTION) {
    $shared_config_to_create = [];

    if (!empty($config_to_create)) {
      $existing_config = $this->getActiveStorages()->listAll();

      // Search the install profile's shared configuration too.
      $shared_storage = $this->getExtensionInstallStorage(static::ID, $collection);

      // Find all shared configuration for enabled modules.
      $shared_config = $this->listConfig($shared_storage, $extensions);
      $shared_config = array_diff($shared_config, $existing_config);
      $shared_config_data = $shared_storage->readMultiple($shared_config);

      // Determine shared configuration to create.
      // Find dependencies of configuration items to be created.
      foreach ($config_to_create as $config_entity) {
        $this->mergeSharedDependencies($config_entity, $shared_config_to_create, $shared_config_data);
      }

    }

    return $shared_config_to_create;
  }

  /**
   * Merges in shared configuration to be created based on dependency
   * information.
   *
   * @param array $config_entity
   *   A configuration entity's data.
   * @param array &$shared_config_to_create
   *   An array of shared configuration to be created, keyed by the
   *   configuration object name; passed by reference.
   * @param array $shared_config_data
   *   An array of available shared configuration data read from the source
   *   storage keyed by the configuration object name.
   */
  protected function mergeSharedDependencies($config_entity, &$shared_config_to_create, $shared_config_data) {
    if (isset($config_entity['dependencies']['config'])) {
      $shared_config_to_add = array_intersect(array_keys($shared_config_data), $config_entity['dependencies']['config']);
      foreach ($shared_config_to_add as $name) {
        $shared_config_to_create[$name] = $shared_config_data[$name];
        // Shared configuration may itself depend on other shared configuration.
        $this->mergeSharedDependencies($shared_config_data[$name], $shared_config_to_create, $shared_config_data);
      }
    }
  }

}
