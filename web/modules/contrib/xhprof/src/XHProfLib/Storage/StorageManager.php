<?php

namespace Drupal\xhprof\XHProfLib\Storage;

/**
 * Class StorageManager
 */
class StorageManager {

  /**
   * @var array
   */
  private $storages;

  /**
   * @return array
   */
  public function getStorages() {
    $output = array();

    /** @var \Drupal\xhprof\XHProfLib\Storage\StorageInterface $storage */
    foreach ($this->storages as $id => $storage) {
      $output[$id] = $storage->getName();
    }

    return $output;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Storage\StorageInterface $storage
   */
  public function addStorage($id, StorageInterface $storage) {
    $this->storages[$id] = $storage;
  }

}
