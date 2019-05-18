<?php

namespace Drupal\nimbus\Storage;

use Drupal\Core\Config\FileStorage;
use Drupal\nimbus\config\ConfigPath;

/**
 * Class StorageFactory.
 *
 * @package Drupal\nimbus\Storage
 */
class StorageFactory {

  /**
   * Constructs a new Storage.
   *
   * @param \Drupal\nimbus\config\ConfigPath $directory
   *   A directory path to use for reading and writing of configuration files.
   * @param string $collection
   *   The collection to store configuration in. Defaults to the
   *   default collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The created storageFactory.
   */
  public function create(ConfigPath $directory, $collection) {
    $value = $directory->getAdditionalInformationByKey('class');
    if (!empty($value)) {
      if ($value instanceof \Closure) {
        return $value((string) $directory, $collection);
      }
      if (is_string($value) && class_exists($value)) {
        return new $value((string) $directory, $collection);
      }
    }
    return new FileStorage((string) $directory, $collection);
  }

}
