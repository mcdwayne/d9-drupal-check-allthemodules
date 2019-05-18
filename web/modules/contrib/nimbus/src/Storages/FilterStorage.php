<?php

namespace Drupal\nimbus\Storages;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class FilterStorage.
 *
 * @package Drupal\nimbus\config
 */
class FilterStorage extends FileStorage {

  /**
   * FilterStorage file constructor.
   *
   * @param string[] $directories
   *   Array with directories.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct($directories, $collection = StorageInterface::DEFAULT_COLLECTION) {
    parent::__construct($directories, $collection);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    global $_nimbus_config_override_directories_regex;
    $data = parent::read($name);

    if (is_array($_nimbus_config_override_directories_regex)) {
      foreach ($_nimbus_config_override_directories_regex as $regex) {
        if (preg_match($regex, $name)) {
          $config_load = \Drupal::service('config.factory')
            ->get($name)
            ->getRawData();

          if (empty($data) && !empty($config_load)) {
            return $config_load;
          }
          else {
            if (!empty($data) && empty($config_load)) {
              return $data;
            }
            return $config_load;
          }
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    global $nimbus_is_export;

    $dir = $this->getCollectionDirectory();
    if (!is_dir($dir)) {
      return [];
    }
    $extension = '.' . static::getFileExtension();

    // glob() directly calls into libc glob(), which is not aware of PHP stream
    // wrappers. Same for \GlobIterator (which additionally requires an absolute
    // realpath() on Windows).
    // @see https://github.com/mikey179/vfsStream/issues/2
    $files = scandir($dir);

    $names = [];
    $pattern = '/^' . preg_quote($prefix, '/') . '.*' . preg_quote($extension, '/') . '$/';
    foreach ($files as $file) {
      if ($file[0] !== '.' && preg_match($pattern, $file)) {
        $names[] = basename($file, $extension);
      }
    }

    if ($this->getCollectionName() == '' && $nimbus_is_export != TRUE) {
      global $_nimbus_config_override_directories_regex;

      if (is_array($_nimbus_config_override_directories_regex)) {
        $source_storage = \Drupal::service('config.storage');
        $source_storage_all = $source_storage->listAll();
        foreach ($_nimbus_config_override_directories_regex as $regex) {
          foreach ($source_storage_all as $element) {
            if (preg_match($regex, $element) && !in_array($element, $names)) {
              $names[] = $element;
            }
          }
        }
      }
    }

    return $names;
  }

}
