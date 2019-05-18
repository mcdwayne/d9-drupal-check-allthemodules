<?php

namespace Drupal\environmental_config\Config;

use Drupal\Core\Config\FileStorage;

/**
 * Provides a factory for creating config file storage objects.
 */
class FileStorageFactory {

  /**
   * Returns a FileStorage object working with the active config directory.
   *
   * @return \Drupal\Core\Config\FileStorage
   *   The FileStorage.
   *
   * @deprecated in Drupal 8.0.x and will be removed before 9.0.0. Drupal core
   * no longer creates an active directory.
   */
  public static function getActive() {
    return new FileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
  }

  /**
   * Returns a FileStorage object working with the sync config directory.
   *
   * @return \Drupal\Core\Config\FileStorage
   *   The FileStorage.
   */
  public static function getSync() {
    $tmpFolderManager = \Drupal::service('environmental_config.tmpfoldermanager');
    return new FileStorage($tmpFolderManager->determineFolder());
  }

}
