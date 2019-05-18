<?php

namespace Drupal\config_overlay\Config;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\UnsupportedDataTypeConfigException;

/**
 * Class to access optional configuration in extensions.
 *
 * This checks the configuration data to only list and return configuration
 * whose dependencies are met.
 */
class ExtensionOptionalStorage extends ExtensionInstallStorage {

  /**
   * The extension configuration storage for the 'config/install' directory.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $extensionInstallStorage;

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::__construct().
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The active configuration store where the list of enabled modules and
   *   themes is stored.
   * @param \Drupal\Core\Config\StorageInterface $extension_install_storage
   *   The extension configuration storage for the 'config/install' directory.
   * @param string $profile
   *   The current installation profile.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   * @param bool $include_profile
   *   (optional) Whether to include the install profile in extensions to
   *   search and to get overrides from.
   */
  public function __construct(StorageInterface $config_storage, StorageInterface $extension_install_storage, $profile, $collection = StorageInterface::DEFAULT_COLLECTION, $include_profile = TRUE) {
    parent::__construct($config_storage, InstallStorage::CONFIG_OPTIONAL_DIRECTORY, $collection, $include_profile, $profile);

    $this->extensionInstallStorage = $extension_install_storage;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      parent::getAllFolders();

      // Iterate over a copy of the array as we are modifying the array within
      // the loop.
      $folders = $this->folders;
      foreach ($folders as $name => $folder) {
        if (!$this->isValidOptionalConfig($name)) {
          unset($this->folders[$name]);
        }
      }
    }
    return $this->folders;
  }

  /**
   * Determines whether there is valid optional configuration for a given name.
   *
   * Valid means that all of its dependencies are met and that it would, thus,
   * would have been installed by the configuration installer if the set of
   * extensions given in the storage were installed.
   *
   * @param string $name
   *   The configuration name.
   *
   * @return bool
   *   TRUE if there is valid optional configuration for the given name; FALSE
   *   otherwise.
   *
   * @throws \Drupal\config_overlay\Exception\ConfigOverlayMissingExtensionListException
   *   If no storage with an extension could be found.
   */
  protected function isValidOptionalConfig($name) {
    /* @see \Drupal\Core\Config\InstallStorage::getFilePath() */
    $filepath = $this->folders[$name] . '/' . $name . '.' . $this->getFileExtension();

    // We cannot call FileStorage::read() directly here, because that would
    // lead to recursion, to due InstallStorage::getFilePath().
    /* @see \Drupal\Core\Config\FileStorage::read() */
    if (!$data = $this->fileCache->get($filepath)) {
      $data = file_get_contents($filepath);
      try {
        $data = $this->decode($data);
      }
      catch (InvalidDataTypeException $e) {
        throw new UnsupportedDataTypeConfigException('Invalid data type in config ' . $name . ', found in file' . $filepath . ' : ' . $e->getMessage());
      }
    }
    $this->fileCache->set($filepath, $data);

    $extension_data = $this->configStorage->read('core.extension') + [
      'module' => [],
      'theme' => [],
    ];
    /* @see \Drupal\Core\Config\ConfigInstaller::getEnabledExtensions() */
    $enabled_extensions = array_merge(
      ['core'],
      array_keys($extension_data['module']),
      array_keys($extension_data['theme'])
    );

    list($provider) = explode('.', $name, 2);
    /* @see \Drupal\Core\Config\ConfigInstaller::validateDependencies() */
    if (!isset($data['dependencies'])) {
      // Simple config or a config entity without dependencies.
      return in_array($provider, $enabled_extensions, TRUE);
    }

    /* @see \Drupal\Core\Config\ConfigInstaller::getMissingDependencies() */
    $dependencies = $data['dependencies'] + [
      'module' => [],
      'theme' => [],
      'config' => [],
    ];
    // Ensure enforced dependencies are included.
    if (isset($dependencies['enforced'])) {
      $dependencies = array_merge_recursive($dependencies, $dependencies['enforced']);
      unset($dependencies['enforced']);
    }
    // Ensure the configuration entity type provider is in the list of
    // dependencies.
    if (!in_array($provider, $dependencies['module'], TRUE)) {
      $dependencies['module'][] = $provider;
    }

    $required_extensions = array_merge($dependencies['module'], $dependencies['theme']);
    if (array_diff($required_extensions, $enabled_extensions)) {
      return FALSE;
    }

    $extension_config = $this->extensionInstallStorage->listAll();
    foreach (array_diff($dependencies['config'], $extension_config) as $missing_config) {
      if (!$this->isValidOptionalConfig($missing_config)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    $extension_install_storage = $this->extensionInstallStorage->createCollection($collection);
    if ($collection !== static::DEFAULT_COLLECTION) {
      $extension_install_storage = new ReadOnlyUnionStorage([
        $extension_install_storage,
        $extension_install_storage->createCollection(static::DEFAULT_COLLECTION)
      ]);
    }
    return new static(
      $this->configStorage,
      $extension_install_storage,
      $this->installProfile,
      $collection,
      $this->includeProfile
    );
  }

}
