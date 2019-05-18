<?php

namespace Drupal\nimbus\Events;

use Drupal\nimbus\config\ConfigPath;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ConfigDetectionPathEvent.
 *
 * @package Drupal\nimbus\Events
 */
class ConfigDetectionPathEvent extends Event {
  /**
   * A array of ConfigPath objects.
   *
   * @var \Drupal\nimbus\config\ConfigPath[]
   */
  protected $fileStorages = [];

  /**
   * Getter for fileStorages.
   *
   * @return \Drupal\nimbus\config\ConfigPath[]
   *   Return a array of ConfigPath elements.
   */
  public function getFileStorages() {
    return $this->fileStorages;
  }

  /**
   * Setter for fileStorage.
   *
   * @param \Drupal\nimbus\config\ConfigPath[] $fileStorages
   *   The ConfigPath array that should be override the current content.
   */
  public function setFileStorages($fileStorages) {
    $this->fileStorages = $fileStorages;
  }

  /**
   * Add function for ConfigPath array's.
   *
   * @param \Drupal\nimbus\config\ConfigPath[] $fileStorages
   *   The ConfigPath elements that should be added to the event.
   */
  public function addFileStorage(array $fileStorages) {
    foreach ($fileStorages as $fileStorage) {
      if (is_string($fileStorage)) {
        $this->fileStorages[] = new ConfigPath($fileStorage);
      }
      else {
        $this->fileStorages[] = $fileStorage;
      }
    }
  }

}
