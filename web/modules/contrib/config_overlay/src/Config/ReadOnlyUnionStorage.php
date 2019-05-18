<?php

namespace Drupal\config_overlay\Config;

use Drupal\config_filter\Exception\UnsupportedMethod;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides a read-only union configuration storage.
 *
 * It is constructed with a list of storages and acts as the union of them. If
 * a configuration object exists in multiple storages, the first storage in the
 * list that has that configuration will be used.
 */
class ReadOnlyUnionStorage implements StorageInterface {

  /**
   * The storages that are part of the union.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $storages = [];

  /**
   * Constructs a read-only union configuration storage.
   *
   * @param \Drupal\Core\Config\StorageInterface[] $storages
   *   The storages to form the union of.
   */
  public function __construct(array $storages) {
    $this->storages = $storages;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    foreach ($this->storages as $storage) {
      if ($storage->exists($name)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    foreach ($this->storages as $storage) {
      $data = $storage->read($name);
      if ($data) {
        return $data;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $data = [];
    $remaining_names = $names;
    foreach ($this->storages as $storage) {
      if (!$remaining_names) {
        break;
      }
      $data += $storage->readMultiple($names);
      $remaining_names = array_diff($names, array_keys($data));
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    throw new UnsupportedMethod(__METHOD__ . ' is not allowed on a ' . static::class);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    throw new UnsupportedMethod(__METHOD__ . ' is not allowed on a ' . static::class);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    throw new UnsupportedMethod(__METHOD__ . ' is not allowed on a ' . static::class);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    $storage = reset($this->storages);
    if (!$storage) {
      throw new UnsupportedMethod(__METHOD__ . ' is not allowed on an empty ' . static::class);
    }
    return $storage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    $storage = reset($this->storages);
    if (!$storage) {
      throw new UnsupportedMethod(__METHOD__ . ' is not allowed on an empty ' . static::class);
    }
    return $storage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $names = [];
    foreach ($this->storages as $storage) {
      $names = array_merge($names, $storage->listAll($prefix));
    }
    return array_unique($names);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    throw new UnsupportedMethod(__METHOD__ . ' is not allowed on a ' . static::class);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    $storages = [];
    foreach ($this->storages as $storage) {
      $storages[] = $storage->createCollection($collection);
    }
    return new static($storages);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $names = [];
    foreach ($this->storages as $storage) {
      $names = array_merge($names, $storage->getAllCollectionNames());
    }
    return array_unique($names);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    $storage = reset($this->storages);
    if (!$storage) {
      throw new UnsupportedMethod(__METHOD__ . ' is not allowed on an empty ' . static::class);
    }
    return $storage->getCollectionName();
  }

}
