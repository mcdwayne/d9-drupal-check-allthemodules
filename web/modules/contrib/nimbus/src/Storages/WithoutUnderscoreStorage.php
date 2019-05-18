<?php

namespace Drupal\nimbus\Storages;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class WithoutUnderscoreStorage.
 *
 * @package Drupal\nimbus\Storages
 */
class WithoutUnderscoreStorage extends FileStorage {

  /**
   * WithoutUnderscoreStorage file constructor.
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
  public function write($name, array $data) {
    if (isset($data['_core'])) {
      unset($data['_core']);
    }
    parent::write($name, $data);
  }

}
