<?php

namespace Drupal\nimbus\config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\nimbus\Storage\StorageFactory;

/**
 * Class ProxyFileStorage.
 *
 * @package Drupal\nimbus\config
 */
class ProxyFileStorage extends FileStorage {

  /**
   * All FileStorages.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  private $fileStorages;

  /**
   * All directories.
   *
   * @var ConfigPath[]
   */
  private $directories;

  /**
   * The storage factory.
   *
   * @var \Drupal\nimbus\Storage\StorageFactory
   */
  private $storageFactory;

  /**
   * ProxyFileStorage constructor.
   *
   * @param ConfigPath[] $directories
   *   Array with directories.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   * @param \Drupal\nimbus\Storage\StorageFactory $storage_factory
   *   (optional) The storage factory to create a new storage. Defaults to NULL.
   */
  public function __construct(array $directories, $collection = StorageInterface::DEFAULT_COLLECTION, StorageFactory $storage_factory = NULL) {
    parent::__construct(config_get_config_directory(CONFIG_SYNC_DIRECTORY), $collection);
    $this->storageFactory = $storage_factory;
    foreach ($directories as $directory) {
      if (is_dir(((string) $directory))) {
        $this->directories[] = $directory;
        $this->fileStorages[] = $this->storageFactory->create($directory, $collection);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    foreach ($this->fileStorages as $fileStorage) {
      $response = $fileStorage->exists($name);
      if ($response == TRUE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $response = FALSE;
    foreach ($this->fileStorages as $key => $fileStorage) {
      if ($this->directories[$key]->hasReadPermission($name)) {
        $read = $fileStorage->read($name);
        if ($read != FALSE) {
          $response = $read;
        }
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = [];
    foreach ($this->fileStorages as $key => $fileStorage) {
      if ($this->directories[$key]->hasReadPermission($names)) {
        $list = $fileStorage->readMultiple($names) + $list;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    $directories = $this->directories;
    foreach (array_reverse($directories, TRUE) as $key => $element) {
      /** @var \Drupal\nimbus\config\ConfigPathPermissionInterface $element */
      if ($element->hasWritePermission($name, $data)) {
        $this->fileStorages[$key]->write($name, $data);
        return;
      }
    }
    throw new \Exception('No Validate directory found');
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    foreach ($this->fileStorages as $key => $fileStorage) {
      if ($fileStorage->exists($name) && $this->directories[$key]->hasDeletePermission($name)) {
        $fileStorage->delete($name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $list = [];
    foreach ($this->fileStorages as $fileStorage) {
      $list = array_merge($fileStorage->listAll($prefix), $list);
    }
    array_unique($list);
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new ProxyFileStorage($this->directories, $collection, $this->storageFactory);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $list = [];
    foreach ($this->fileStorages as $fileStorage) {
      $list = array_merge($fileStorage->getAllCollectionNames(), $list);
    }
    array_unique($list);
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    $response = '';
    foreach ($this->fileStorages as $fileStorage) {
      $response = $fileStorage->getCollectionName();
    }
    return $response;
  }

  /**
   * The active write directory.
   *
   * @return string
   *   The current active write directories.
   *
   * @throws \Exception
   *    If the number of active write directories is less than 0.
   */
  public function getWriteDirectories() {
    $directories = $this->directories;
    $empty_array = [];
    do {
      if (count($directories) < 0) {
        throw new \Exception('No directory defined');
      }
      $element = array_pop($directories);
    } while (!$element->hasWritePermission(NULL, $empty_array));
    return (string) $element;
  }

  /**
   * Returns the path to the configuration file.
   *
   * @param string $name
   *   The name of the configuration file.
   *
   * @return string
   *   The path to the configuration file.
   */
  public function getFilePath($name) {
    $i = 0;
    $return_value = [];
    foreach ($this->fileStorages as $fileStorage) {
      $response = $fileStorage->exists($name);
      if ($response == TRUE) {
        $return_value[] = (string) $this->directories[$i];
      }
      $i++;
    }
    return implode("\n", $return_value);
  }

}
