<?php

namespace Drupal\nimbus\config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\nimbus\Events\ConfigDetectionPathEvent;
use Drupal\nimbus\NimbusEvents;

/**
 * Provides a factory for creating config file storage objects.
 */
class FileStorageFactoryAlter {

  /**
   * Returns a FileStorage object working with the active config directory.
   *
   * @return \Drupal\Core\Config\FileStorage
   *   Return a config file storage.
   *
   * @deprecated in Drupal 8.0.x and will be removed before 9.0.0. Drupal core
   * no longer creates an active directory.
   */
  public static function getActive() {
    return new ProxyFileStorage([new FileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY))]);
  }

  /**
   * Returns a FileStorage object working with the sync config directory.
   *
   * @return \Drupal\Core\Config\FileStorage
   *   Return a config file storage.
   */
  public static function getSync() {
    $event = new ConfigDetectionPathEvent();
    \Drupal::service('event_dispatcher')
      ->dispatch(NimbusEvents::ADD_PATH, $event);

    $storage_factory = \Drupal::service('nimbus.storage_factory');
    $proxy_file_storage = new ProxyFileStorage($event->getFileStorages(), StorageInterface::DEFAULT_COLLECTION, $storage_factory);

    return $proxy_file_storage;
  }

}
