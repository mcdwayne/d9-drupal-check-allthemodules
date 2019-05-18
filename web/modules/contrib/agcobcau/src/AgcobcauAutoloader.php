<?php

namespace Drupal\agcobcau;

use Drupal\Component\PhpStorage\PhpStorageInterface;

class AgcobcauAutoloader {

  /**
   * @var \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\agcobcau\AgcobcauAutoloader
   */
  protected  static $instance;

  /**
   * Construct an agcobcau autoloader object.
   *
   * @param \Drupal\Component\PhpStorage\PhpStorageInterface $storage
   */
  public function __construct(PhpStorageInterface $storage) {
    $this->storage = $storage;
    static::$instance = $this;
  }

  /**
   * Handles autoloading our auto-generated classes.
   *
   * @param string $class
   *   The class name.
   */
  public function autoload($class) {
    $name = str_replace('\\', '/', $class);
    if (substr($class, 0, 23) === 'Drupal\agcobcau\Entity\\' && $this->storage->exists($name)) {
      $this->storage->load($name);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Set the storage for the class loader.
   *
   * @param \Drupal\Component\PhpStorage\PhpStorageInterface $storage
   *   The php storage.
   */
  public function setStorage(PhpStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * Get the class loader instance.
   *
   * @return \Drupal\agcobcau\AgcobcauAutoloader
   *   This class instance.
   */
  public static function getInstance() {
    return static::$instance;
  }

}
