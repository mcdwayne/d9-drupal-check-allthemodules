<?php

namespace Drupal\nimbus\EventSubscriber\FileDetection;

use Drupal\nimbus\config\ConfigPath;
use Drupal\nimbus\config\ConfigPathWithPermission;
use Drupal\nimbus\Events\ConfigDetectionPathEvent;
use Drupal\nimbus\NimbusEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConstantDirectoriesSubscriber.
 *
 * @package Drupal\nimbus\EventSubscriber\FileDetection
 */
class ConstantDirectoriesSubscriber implements EventSubscriberInterface {

  /**
   * Create config include pathes from a global variable.
   *
   * The following option allowed in the global variable.
   *    - A instanceof ConfigPath.
   *      $_nimbus_config_override_directories =[
   *         new ConfigPath('some/other/places'),
   *      ]
   *    - A config path array like (by default is everything true).
   *      $_nimbus_config_override_directories =[
   *         [
   *            (required)path = 'some/path/'
   *            (optional)readPermission = true // only true or false.
   *            (optional)writePermission = true // only true or false.
   *            (optional)deletePermission = true // only true or false.
   *            (optional)class = '' // A class string or a closure.
   *         ]
   *      ]
   *     - A simple path string.
   *      $_nimbus_config_override_directories =[
   *         'some/crazy/places'
   *      ]
   *
   * @param \Drupal\nimbus\Events\ConfigDetectionPathEvent $event
   *   The event object.
   */
  public function onPreCreateFileConfigManager(ConfigDetectionPathEvent $event) {
    global $_nimbus_config_override_directories;
    $file_storages = [];

    $file_storages[] = config_get_config_directory(CONFIG_SYNC_DIRECTORY);

    if (isset($_nimbus_config_override_directories)) {
      if (is_array($_nimbus_config_override_directories)) {
        foreach ($_nimbus_config_override_directories as $directory) {
          if (is_array($directory)) {
            if (isset($directory['path'])) {

              if (in_array($directory['path'], $file_storages)) {
                unset($file_storages[array_search($directory['path'], $file_storages)]);
              }

              $readPermission = TRUE;
              $writePermission = TRUE;
              $deletePermission = TRUE;
              if (isset($directory['readPermission'])) {
                $readPermission = $directory['readPermission'];
              }
              if (isset($directory['writePermission'])) {
                $writePermission = $directory['writePermission'];
              }
              if (isset($directory['deletePermission'])) {
                $deletePermission = $directory['deletePermission'];
              }
              $storage_element = new ConfigPathWithPermission(
                $directory['path'],
                $readPermission,
                $writePermission,
                $deletePermission
              );
              if (isset($directory['class'])) {
                $storage_element->addAdditionalInformation('class', $directory['class']);
              }

              $file_storages[] = $storage_element;
            }
          }
          elseif ($directory instanceof ConfigPath) {
            if (in_array((string) $directory, $file_storages)) {
              unset($file_storages[array_search((string) $directory, $file_storages)]);
            }
            $file_storages[] = $directory;
          }
          else {
            if (in_array((string) $directory, $file_storages)) {
              unset($file_storages[array_search((string) $directory, $file_storages)]);
            }
            $file_storages[] = new ConfigPath($directory);
          }
        }
      }
    }

    $event->addFileStorage($file_storages);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NimbusEvents::ADD_PATH][] = ['onPreCreateFileConfigManager', 1];
    return $events;
  }

}
