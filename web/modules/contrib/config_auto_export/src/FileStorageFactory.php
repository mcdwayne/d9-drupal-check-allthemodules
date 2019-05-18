<?php

namespace Drupal\config_auto_export;

use Drupal\Core\Config\FileStorage;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a factory for creating config file storage objects.
 */
class FileStorageFactory {

  /**
   * Returns the configured directory name.
   *
   * @return string
   *   The directory name.
   */
  public static function getDirectory() {
    return \Drupal::config('config_auto_export.settings')->get('directory');
  }

  /**
   * Returns a FileStorage object working with the configured directory.
   *
   * @return \Drupal\Core\Config\FileStorage
   *   Public static function getSync.
   */
  public static function getSync() {
    return new FileStorage(self::getDirectory());
  }

  /**
   * Removes the configured directory recursivly.
   */
  public static function removeSync() {
    $directory = self::getDirectory();
    if (is_dir($directory)) {
      $fs = new Filesystem();
      $fs->remove($directory);
    }
  }

}
