<?php

namespace Drupal\nimbus\Storages;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class DevelopmentStorage.
 *
 * @package Drupal\nimbus\config
 */
class DevelopmentStorage extends FileStorage {

  /**
   * A list of modules names.
   *
   * @var string[]
   */
  protected $modules = [];

  /**
   * Redis file constructor.
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
    $data = parent::read($name);
    if ($name === 'core.extension' && isset($data['module'])) {
      foreach ($this->modules as $module => $weight) {
        $data['module'][$module] = 0;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    if ($name === 'core.extension' && isset($data['module'])) {
      foreach ($this->modules as $module => $weight) {
        unset($data['module'][$module]);
      }
    }
    parent::write($name, $data);
  }

  /**
   * Add a module to white/blacklist.
   *
   * @param string $module_name
   *   The module name.
   * @param string $weight
   *   The weight.
   */
  public function addModuleToBlacklist($module_name, $weight) {
    $this->modules[$module_name] = $weight;
  }

}
