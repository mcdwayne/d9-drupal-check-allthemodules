<?php

namespace Drupal\config_overlay\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\config_overlay\Config\ReadOnlyUnionStorage;
use Drupal\config_overlay\Config\ExtensionOptionalStorage;
use Drupal\config_overlay\Exception\ConfigOverlayMissingExtensionListException;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overlay configuration filter.
 *
 * This will turn the synchronization storage into an overlay of the shipped
 * configuration of all enabled extensions.
 *
 * When writing configuration this filter will skip any configuration that is
 * identical to configuration provided by an installed extension. Thus, only
 * configuration that has been added or modified will be written to the
 * synchronization directory. When reading configuration the extensions'
 * configuration is amended to the configuration in the directory, so that the
 * missing files are transparent and configuration import, for example, works as
 * expected.
 *
 * Because in contrast to the active configuration shipped configuration
 * generally does not contain a UUID and certainly does not contain a default
 * configuration hash, the 'uuid' and '_core' keys are ignored when comparing
 * the active configuration with the extension configuration to determine
 * whether or not they are equal. When reading the extension configuration as
 * part of the overlay, the respective UUIDs and hashes of the active
 * configuration are amended automatically so that the configuration import does
 * not detect any differences relative to the active configuration.
 *
 * Note that due to this the configuration system will not be able to detect
 * configuration that has been deleted and recreated with the same name when
 * this filter is active. Such configuration will be detected as an update. If
 * configuration is recreated to be exactly the same as before (but for the
 * UUID) this will not be detected as a change at all if this filter is active.
 *
 * Deleting a shipped configuration entity is supported by writing an empty file
 * to the configuration synchronization directory. Due to the way this deletion
 * works, this filter must be run last, at least among all filters that wish to
 * filter the deletion process. For this reason, this filter declares a weight
 * of 100.
 *
 * @todo Note that exporting configuration via Drush does not work in a way that
 *   that supports the writing of empty files as described above.
 *
 * @ConfigFilter(
 *   id = "config_overlay",
 *   label = @Translation("Module and installation profile configuration overlay"),
 *   weight = 100,
 * )
 */
class ConfigOverlayFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The extension storage.
   *
   * This is a chain storage that wraps an extension storage for the
   * 'config/install' directories and an extension storage for the
   * 'config/optional' directories.
   *
   * @var \Drupal\Core\Config\StorageInterface
   *
   * @see \Drupal\Core\Config\ExtensionInstallStorage
   * @see \Drupal\config_overlay\Config\ExtensionOptionalStorage
   */
  protected $extensionStorage;

  /**
   * Configuration keys to ignore in the source configuration.
   *
   * In case any of these keys are present in the source configuration but not
   * in the extension configuration, the configuration will still be considered
   * equal.
   *
   * @var string[]
   */
  protected $keysToIgnore = ['_core', 'uuid'];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an extension configuration filter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, StorageInterface $active_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fileSystem = $file_system;

    $this->activeStorage = $active_storage;
    // The extension storages depends on the source storage, so they cannot be
    // set at this point.
    /* @see \Drupal\config_overlay\Plugin\ConfigFilter\ConfigOverlayFilter::setSourceStorage() */
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('config.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceStorage(StorageInterface $source_storage) {
    parent::setSourceStorage($source_storage);

    // The extension storage depends on the source storage to read the list of
    // installed extensions so initialize it as soon as the source storage is
    // available. When this filter is created for a non-default collection of
    // the source storage, a respective collection of the extension storage will
    // be created explicitly.
    /* @see \Drupal\config_overlay\Plugin\ConfigFilter\ExtensionConfigFilter::filterCreateCollection() */
    if ($this->getSourceStorage()->getCollectionName() === StorageInterface::DEFAULT_COLLECTION) {
      // The extension install storage requires that the passed storage contains
      // the extension list in order to work properly.
      /* @see \Drupal\Core\Config\ExtensionInstallStorage::getAllFolders */
      $storage = $this->getStorageWithExtensionList();
      $extension_config = $storage->read('core.extension') + ['profile' => NULL];
      $profile = $extension_config['profile'];

      $extension_install_storage = new ExtensionInstallStorage(
        $storage,
        InstallStorage::CONFIG_INSTALL_DIRECTORY,
        $storage->getCollectionName(),
        TRUE,
        $profile
      );

      $storages = [];
      // If the profile has a config/sync directory add that first, so that
      // configuration there can override module-provided configuration.
      /* @see install_profile_info() */
      $profile_sync_path = drupal_get_path('profile', $profile) . '/config/sync';
      if (is_dir($profile_sync_path)) {
        $storages[] = new FileStorage($profile_sync_path, $storage->getCollectionName());
      }
      $storages[] = $extension_install_storage;
      $storages[] = new ExtensionOptionalStorage(
        $storage,
        $extension_install_storage,
        $profile,
        $storage->getCollectionName()
      );

      $this->setExtensionStorage(new ReadOnlyUnionStorage($storages));
    }
  }

  /**
   * Sets the extension install configuration storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $extension_storage
   *   The extension install configuration storage.
   */
  protected function setExtensionStorage(StorageInterface $extension_storage) {
    $this->extensionStorage = $extension_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // In order to support deleting shipped configuration an empty file is
    // written to the source storage. That means, however, that the storage will
    // discover it as existing, but not return any data when reading it. This
    // makes sure that the filtered storage will not consider such configuration
    // to be existing.
    if ($exists && !$this->getSourceStorage()->read($name)) {
      return FALSE;
    }

    return $exists || $this->extensionStorage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function filterWriteEmptyIsDelete($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function filterDelete($name, $delete) {
    // If another configuration filter has already denied deletion, do not do
    // anything.
    if (!$delete) {
      return FALSE;
    }

    if ($this->extensionStorage->exists($name)) {
      // @todo Ideally we would use the source storage directly, but that is not
      //   the file storage itself but a read-only storage wrapping of (a cached
      //   storage wrapping of) the file storage.
      /* @see \Drupal\Core\Config\FileStorageFactory::getSync() */
      $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
      $sync_storage = new FileStorage($directory, $this->getSourceStorage()->getCollectionName());
      // If overridden configuration is being deleted, we still need to write an
      // empty file to the synchronization directory to prevent this filter
      // from considering the respective shipped configuration after the
      // deletion. We must remove the existing file first, however. This is
      // unfortunate, because it effectively removes the possibility of any
      // later configuration filter to deny the deletion.
      /* @see \Drupal\config_filter\Config\FilteredStorage::delete() */
      if ($sync_storage->read($name)) {
        $sync_storage->delete($name);
      }
      $this->writeToFileStorage($sync_storage, $directory, $name, '');

      return FALSE;
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    // If the configuration does not exist in the sync directory, look it up
    // in the extensions' configuration.
    if (!$data) {
      $data = $this->extensionStorage->read($name);

      // Amend the exported configuration with the UUID and hash of the active
      // configuration.
      if ($data && ($active_data = $this->activeStorage->read($name))) {
        $this->processExtensionDataOnRead($data, $active_data);
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    // Look up any missing configuration in the extensions' configuration.
    $remaining_names = array_diff($names, array_keys($data));
    $data += $this->extensionStorage->readMultiple($remaining_names);

    // Amend the exported configuration with the UUID and hash of the active
    // configuration.
    $active_data = $this->activeStorage->readMultiple($remaining_names);
    foreach ($remaining_names as $remaining_name) {
      if (isset($data[$remaining_name]) && isset($active_data[$remaining_name])) {
        $this->processExtensionDataOnRead($data[$remaining_name], $active_data[$remaining_name]);
      }
    }
    // The data does not need to be sorted, as FilteredStorage::readMultiple()
    // does that already.
    /* @see \Drupal\config_filter\Config\FilteredStorage::readMultiple() */
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    $data_to_compare = $data;

    $extension_data = $this->extensionStorage->read($name);

    foreach ($this->keysToIgnore as $key_to_ignore) {
      if (!isset($extension_data[$key_to_ignore])) {
        unset($data_to_compare[$key_to_ignore]);
      }
    }

    // Do not write anything if the active configuration matches configuration
    // provided by an extension.
    if ($data_to_compare === $extension_data) {
      return;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $extension_data = array_diff($this->extensionStorage->listAll($prefix), $data);
    $data = array_filter($data, [$this->getFilteredStorage(), 'exists']);
    $data = array_merge($data, $extension_data);

    // The data does not need to be sorted, as FilteredStorage::listAll()
    // does that already.
    /* @see \Drupal\config_filter\Config\FilteredStorage::listAll() */
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDeleteAll($prefix, $delete) {
    if (!$delete) {
      return FALSE;
    }

    if ($this->extensionStorage->listAll($prefix)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    $filter = new static(
      $this->configuration,
      $this->pluginId,
      $this->pluginDefinition,
      $this->fileSystem,
      $this->activeStorage->createCollection($collection)
    );
    $filter->setExtensionStorage($this->extensionStorage->createCollection($collection));
    return $filter;
  }

  /**
   * Writes encoded data to a file storage instance for a given directory.
   *
   * It is not possible to write an empty file using FileStorage::write(), so
   * this is a copy of that method without the data encoding part.
   *
   * @param \Drupal\Core\Config\FileStorage $storage
   *   The file storage to write to.
   * @param string $base_dir
   *   The directory the storage was instantiated for.
   * @param string $name
   *   The configuration name to write.
   * @param string $encoded_data
   *   The encoded data to write.
   *
   * @see \Drupal\Core\Config\FileStorage::write()
   *
   * @todo Refactor this into its own storage class extending FileStorage
   */
  protected function writeToFileStorage(FileStorage $storage, $base_dir, $name, $encoded_data) {
    $target = $storage->getFilePath($name);
    $status = @file_put_contents($target, $encoded_data);
    if ($status === FALSE) {
      // Try to make sure the directory exists and try writing again.
      /* @see \Drupal\Core\Config\FileStorage::getCollectionDirectory() */
      if ($storage->getCollectionName() == StorageInterface::DEFAULT_COLLECTION) {
        $dir = $base_dir;
      }
      else {
        $dir = $base_dir . '/' . str_replace('.', '/', $storage->getCollectionName());
      }
      /* @see \Drupal\Core\Config\FileStorage::ensureStorage() */
      $success = $this->fileSystem->prepareDirectory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      // Only create .htaccess file in root directory.
      if ($dir == $base_dir) {
        $success = $success && file_save_htaccess($base_dir, TRUE, TRUE);
      }
      if (!$success) {
        throw new StorageException('Failed to create config directory ' . $dir);
      }
      $status = @file_put_contents($target, $encoded_data);
    }
    if ($status === FALSE) {
      throw new StorageException('Failed to write configuration file: ' . $storage->getFilePath($name));
    }
    else {
      $this->fileSystem->chmod($target);
    }

    // Make sure the file cache is populated.
    $storage->read($name);
  }

  /**
   * Adds ignored data from the active configuration to the exported data.
   *
   * @param array $extension_data
   *   The respective extension configuration data that is being read.
   * @param array $active_data
   *   The respective active configuration data.
   */
  public function processExtensionDataOnRead(array &$extension_data, array $active_data) {
    foreach ($this->keysToIgnore as $key_to_ignore) {
      // The system.site configuration specifies an empty UUID, so checking
      // whether the 'uuid' key is set is not sufficient.
      if (empty($extension_data[$key_to_ignore]) && isset($active_data[$key_to_ignore])) {
        $extension_data[$key_to_ignore] = $active_data[$key_to_ignore];
      }
    }

    // Make sure that the ignored data is positioned in the same place in the
    // data array as it is in the active configuration so that strict equality
    // between the exported and active configuration can be achieved. The
    // intersection makes sure that other keys that are available in the active
    // configuration but not in the exported configuration are not merged.
    $extension_data = array_intersect_key(array_merge($active_data, $extension_data), $extension_data);
  }

  /**
   * Gets the storage with the extension list.
   *
   * Generally the extension list is read from the source storage, so that
   * configuration will be found in modules that will be installed by importing
   * configuration. Prior to the very first configuration export, however, the
   * source storage is empty, and so the 'core.extension' configuration does not
   * exist either. In this case the extension list is read from the active
   * storage, so that this filter can work at all.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage that contains the extension list.
   *
   * @throws \Drupal\config_overlay\Exception\ConfigOverlayMissingExtensionListException
   *   If no storage with an extension could be found.
   */
  protected function getStorageWithExtensionList() {
    $storage = NULL;
    foreach ([$this->getSourceStorage(), $this->activeStorage] as $possible_storage) {
      if ($possible_storage->exists('core.extension')) {
        return $possible_storage;
      }
    }

    throw new ConfigOverlayMissingExtensionListException('The extension list could not be found in the source or the active storage');
  }

}
