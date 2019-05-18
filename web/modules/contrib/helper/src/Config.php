<?php

namespace Drupal\helper;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Serialization\Yaml;
use Psr\Log\LoggerInterface;

/**
 * Configuration helper.
 */
class Config {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Config constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigManagerInterface $config_manager, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->configManager = $config_manager;
    $this->logger = $logger;
  }

  /**
   * Import a single configuration file.
   *
   * @param string $uri
   *   The path to the file.
   * @param string $contents
   *   Optional manual contents of the file.
   *
   * @throws \RuntimeException
   *   If unable to decode YAML from file.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In unable to save the config.
   */
  public function importFile($uri, $contents = NULL) {
    if (!isset($contents) && (!$contents = @file_get_contents($uri))) {
      throw new \RuntimeException("Unable to read file $uri.");
    }

    $data = Yaml::decode($contents);
    if (!is_array($data)) {
      throw new \RuntimeException("Unable to decode YAML from $uri.");
    }

    $this->logger->notice('Importing @uri.', ['@uri' => $uri]);

    $config_name = basename($uri, '.yml');
    $entity_type_id = $this->configManager->getEntityTypeIdByName($config_name);
    if ($entity_type_id) {
      $entity_storage = $this->getStorage($entity_type_id);
      $entity_id = $this->getEntityId($entity_storage, $config_name);
      $entity_type = $entity_storage->getEntityType();
      $id_key = $entity_type->getKey('id');
      $data[$id_key] = $entity_id;
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
      $entity = $entity_storage->create($data);
      if ($existing_entity = $entity_storage->load($entity_id)) {
        $entity
          ->set('uuid', $existing_entity->uuid())
          ->enforceIsNew(FALSE);
      }
      $entity_storage->save($entity);
    }
    else {
      $this->configFactory->getEditable($config_name)->setData($data)->save();
    }
  }

  /**
   * Import a directory containing configuration files.
   *
   * @param string $directory
   *   The path to the directory
   * @param array $options
   *   An array of options to pass to file_scan_directory().
   *
   * @throws \RuntimeException
   *   If unable to decode YAML from file.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In unable to save the config.
   */
  public function importDirectory($directory, array $options = []) {
    $files = file_scan_directory($directory, '/^.*\.yml$/', $options);
    foreach ($files as $file) {
      $this->importFile($file->uri);
    }
  }

  /**
   * Import a module's directory containing configuration files.
   *
   * @param string $module
   *   The module name.
   * @param string $directory
   *   The directory name inside the module's config directory. Defaults to
   *   'install'.
   * @param array $options
   *   An array of options to pass to file_scan_directory().
   *
   * @throws \RuntimeException
   *   If unable to decode YAML from file.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In unable to save the config.
   */
  public function importModule($module, $directory = 'install', array $options = []) {
    $this->importDirectory(drupal_get_path('module', $module) . '/config/' . $directory, $options);
  }

  /**
   * Export a single configuration file.
   *
   * @param string $config_name
   *   The configuration ID.
   * @param string $directory
   *   The directory that the configuration file will be written to.
   * @param array $options
   *   An optional array of options.
   *
   * @throws \RuntimeException
   *   If the directory is not writeable or if the configuration does not exist.
   *
   * @return bool
   *   TRUE if the file was written successfully, otherwise FALSE.
   */
  public function exportFile($config_name, $directory, array $options = []) {
    if (!is_writeable($directory)) {
      throw new \RuntimeException("The directory $directory is not writeable.");
    }

    $config = $this->configFactory->get($config_name);
    $data = $config->getRawData();
    if (empty($data)) {
      throw new \RuntimeException("The config $config_name does not exist.");
    }

    // Remove _core property.
    unset($data['_core']);

    // Remove UUIDs from exported config.
    if (!empty($options['remove_uuid']) && isset($data['uuid']) && $this->configManager->getEntityTypeIdByName($config_name)) {
      unset($data['uuid']);
    }

    // Merge in additonal information.
    if (!empty($options['merge']) && is_array($options['merge'])) {
      NestedArray::mergeDeep($data, $options['merge']);
    }

    $uri = $directory . '/' . $config_name . '.yml';
    $this->logger->notice('Exporting @uri.', ['@uri' => $uri]);
    return (bool) file_put_contents($uri, Yaml::encode($data));
  }

  /**
   * Re-export a directory containing configuration files.
   *
   * @param string $directory
   *   The path to the directory.
   * @param array $options
   *   An array of options to pass to file_scan_directory().
   *
   * @throws \RuntimeException
   *   If the directory is not writeable or if the configuration does not exist.
   */
  public function reExportDirectory($directory, array $options = []) {
    $files = file_scan_directory($directory, '/^.*\.yml$/', $options);
    foreach ($files as $file) {
      $this->exportFile(basename($file->filename, '.yml'), dirname($file->uri));
    }
  }

  /**
   * Import a module's directory containing configuration files.
   *
   * @param string $module
   *   The module name.
   * @param string $directory
   *   The directory name inside the module's config directory. Defaults to
   *   'install'.
   * @param array $options
   *   An array of options to pass to file_scan_directory().
   *
   * @throws \RuntimeException
   *   If the directory is not writeable or if the configuration does not exist.
   */
  public function reExportModule($module, $directory = 'install', array $options = []) {
    $this->reExportDirectory(drupal_get_path('module', $module) . '/config/' . $directory, $options);
  }

  /**
   * Gets the configuration storage.
   *
   * @param string $entity_type_id
   *   The configuration entity type ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected function getStorage($entity_type_id) {
    return $this->configManager->getEntityManager()->getStorage($entity_type_id);
  }

  /**
   * Get the entity ID for a configuration object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage
   *   The configuration entity storage.
   * @param string $config_name
   *   The configuration name.
   *
   * @return string
   *   The entity ID.
   */
  protected function getEntityId(ConfigEntityStorageInterface $entity_storage, $config_name) {
    // getIDFromConfigName adds a dot but getConfigPrefix has a dot already.
    return $entity_storage::getIDFromConfigName($config_name, $entity_storage->getEntityType()->getConfigPrefix());
  }

}
