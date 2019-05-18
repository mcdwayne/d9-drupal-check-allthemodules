<?php

namespace Drupal\config_provider;

use Drupal\Core\Config\StorageInterface;
use Symfony\Component\Yaml\Inline;

/**
 * Provides an in memory configuration storage.
 */
class InMemoryStorage implements NormalizableStorageInterface {

  /**
   * The configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * The storage collection.
   *
   * @var string
   */
  protected $collection;

  /**
   * Whether data should be normalized on read.
   *
   * @var bool
   */
  protected $normalizeOnRead;

  /**
   * Constructs a new InMemoryStorage.
   *
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   * @param array $config
   *   (optional) The configuration in the storage.
   * @param bool $normalize_on_read
   *   (optional) Whether data should be normalized on read. Defaults to FALSE.
   */
  public function __construct($collection = StorageInterface::DEFAULT_COLLECTION, array $config = [], $normalize_on_read = FALSE) {
    $this->collection = $collection;
    $this->config = $config;
    $this->normalizeOnRead = $normalize_on_read;
    if (!isset($this->config[$collection])) {
      $this->config[$collection] = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizeOnRead() {
    return $this->normalizeOnRead;
  }

  /**
   * {@inheritdoc}
   */
  public function setNormalizeOnRead($normalize_on_read) {
    $this->normalizeOnRead = $normalize_on_read;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return isset($this->config[$this->collection][$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    if ($this->exists($name)) {
      $data = $this->config[$this->collection][$name];
      if ($this->normalizeOnRead) {
        $data = $this->normalizeArray($data);
      }
      return $data;
    }

    return FALSE;
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
    $this->config[$this->collection][$name] = $data;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    if (isset($this->config[$this->collection][$name])) {
      unset($this->config[$this->collection][$name]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    if (!$this->exists($name)) {
      return FALSE;
    }
    $this->config[$this->collection][$new_name] = $this->config[$this->collection][$name];
    unset($this->config[$this->collection][$name]);
    return TRUE;
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
    $names = [];
    if ($prefix === '') {
      $names = array_keys($this->config[$this->collection]);
    }
    else {
      foreach (array_keys($this->config[$this->collection]) as $name) {
        if (strpos($name, $prefix) === 0) {
          $names[] = $name;
        }
      }
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    if ($prefix === '') {
      $this->config[$this->collection] = [];
    }
    else {
      foreach (array_keys($this->config[$this->collection]) as $name) {
        if (strpos($name, $prefix) === 0) {
          unset($this->config[$this->collection][$name]);
        }
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $collection,
      $this->config
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $collections = array_keys($this->config);
    // The default collection is not included here.
    unset($collections[array_search(StorageInterface::DEFAULT_COLLECTION, $collections)]);
    return $collections;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * Writes configuration data to the storage for a collection.
   *
   * @param string $name
   *   The name of a configuration object to save.
   * @param array $data
   *   The configuration data to write.
   * @param string $collection
   *   The collection to store configuration in.
   *
   * @return bool
   *   TRUE on success, FALSE in case of an error.
   */
  public function writeToCollection($name, array $data, $collection) {
    if (!isset($this->config[$collection])) {
      $this->config[$collection] = [];
    }
    $this->config[$collection][$name] = $data;
    return TRUE;
  }

  /**
   * Recursively sorts an array by key, and removes empty arrays.
   *
   * @param array $array
   *   An array to normalize.
   *
   * @return array
   *   An array that is sorted by key, at each level of the array, with empty
   *   arrays removed.
   *
   * @todo Replace this when
   *   https://www.drupal.org/project/config_update/issues/2989991 lands.
   */
  protected function normalizeArray(array $array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $new = $this->normalizeArray($value);
        if (count($new)) {
          $array[$key] = $new;
        }
      }
    }

    // If the array is associative, sort by key.
    if (Inline::isHash($array)) {
      ksort($array);
    }
    // Otherwise, sort by value.
    else {
      sort($array);
    }

    return $array;
  }

}
