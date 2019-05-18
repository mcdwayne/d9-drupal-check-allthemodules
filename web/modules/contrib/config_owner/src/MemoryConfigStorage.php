<?php

declare(strict_types = 1);

namespace Drupal\config_owner;

use Drupal\Core\Config\StorageInterface;

/**
 * A storage that keeps the configuration in memory.
 *
 * This can be used primarily for adhoc comparison of configuration storages.
 */
class MemoryConfigStorage implements StorageInterface {

  /**
   * The config data.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The storage collection.
   *
   * @var string
   */
  protected $collection = StorageInterface::DEFAULT_COLLECTION;

  /**
   * A default storage that can be used to derive configs in other collections.
   *
   * If the MemoryConfigStorage is used inside a StorageComparer, the latter
   * will ask for a version of MemoryConfigStorage for the configs of other
   * collections as well. So this storage is used to derive this config.
   *
   * @var \Drupal\Core\Config\StorageInterface|null
   */
  protected $storage;

  /**
   * MemoryConfigStorage constructor.
   *
   * @param string $collection
   *   The config collection.
   * @param \Drupal\Core\Config\StorageInterface|null $storage
   *   A default storage.
   */
  public function __construct($collection = StorageInterface::DEFAULT_COLLECTION, StorageInterface $storage = NULL) {
    $this->collection = $collection;
    $this->storage = $storage;

    if ($storage && $storage->getCollectionName() !== $collection) {
      $new_storage = $storage->createCollection($collection);
      foreach ($new_storage->listAll() as $name) {
        $this->config[$name] = $new_storage->read($name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return (bool) isset($this->config[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return $this->config[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = [];
    foreach ($names as $name) {
      if ($data = $this->read($name)) {
        $list[$name] = $data;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    $this->config[$name] = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    if (isset($this->config[$name])) {
      unset($this->config[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    if (isset($this->config[$name])) {
      $this->config[$new_name] = $this->config[$name];
      unset($this->config[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $raw;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $all = array_keys($this->config);
    if ($prefix === '') {
      return $all;
    }

    $list = [];
    foreach ($all as $name) {
      if (strpos($name, $prefix) === 0) {
        $list[] = $name;
      }
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    if ($prefix === '') {
      $this->config = [];
      return TRUE;
    }

    foreach ($this->config as $name => $value) {
      if (strpos($name, $prefix) === 0) {
        unset($this->config[$name]);
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static($collection, $this->storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    return [$this->collection];
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

}
