<?php

namespace Drupal\prepared_data\Storage;

/**
 * Trait for working with the shorthand storage.
 */
trait ShorthandStorageTrait {

  /**
   * The shorthand storage.
   *
   * @var \Drupal\prepared_data\Storage\ShorthandStorageInterface
   */
  protected $shorthandStorage;

  /**
   * Get the shorthand storage.
   *
   * @return \Drupal\prepared_data\Storage\ShorthandStorageInterface
   *   The shorthand storage.
   */
  public function getShorthandStorage() {
    if (!isset($this->shorthandStorage)) {
      $this->shorthandStorage = \Drupal::service('prepared_data.shorthand_storage');
    }
    return $this->shorthandStorage;
  }

  /**
   * Set the shorthand storage.
   *
   * @param \Drupal\prepared_data\Storage\ShorthandStorageInterface $storage
   *   The shorthand storage to set.
   */
  public function setShorthandStorage(ShorthandStorageInterface $storage) {
    $this->shorthandStorage = $storage;
  }

}
