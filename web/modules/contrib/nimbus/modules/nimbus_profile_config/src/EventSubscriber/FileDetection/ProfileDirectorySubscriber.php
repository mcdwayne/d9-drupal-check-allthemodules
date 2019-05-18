<?php

namespace Drupal\nimbus_profile_config\EventSubscriber\FileDetection;

use Drupal\Core\Config\InstallStorage;
use Drupal\nimbus\config\ConfigPath;
use Drupal\nimbus\Events\ConfigDetectionPathEvent;
use Drupal\nimbus\NimbusEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProfileDirectorySubscriber.
 *
 * @package Drupal\nimbus_profile_config\EventSubscriber\FileDetection
 */
class ProfileDirectorySubscriber implements EventSubscriberInterface {

  /**
   * Add the active profile to the ConfigPath array.
   *
   * @param \Drupal\nimbus\Events\ConfigDetectionPathEvent $event
   *   The event object.
   */
  public function onPreCreateFileConfigManager(ConfigDetectionPathEvent $event) {
    $file_storages = [];

    $extension_path = $this->drupalGetPath('profile', drupal_get_profile()) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    $file_storages[] = new ConfigPath($extension_path);

    $event->addFileStorage($file_storages);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NimbusEvents::ADD_PATH][] = ['onPreCreateFileConfigManager', 10];
    return $events;
  }

  /**
   * Wrapper for drupal_get_path().
   *
   * @param string $type
   *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
   *   'theme_engine'.
   * @param string $name
   *   The name of the item for which the path is requested. Ignored for
   *   $type 'core'.
   *
   * @return string
   *   The path to the requested item or an empty string if the item is not
   *   found.
   */
  protected function drupalGetPath($type, $name) {
    return drupal_get_path($type, $name);
  }

}
