<?php

namespace Drupal\nimbus\Storages;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class RedisStorage.
 *
 * @package Drupal\nimbus\config
 */
class RedisStorage extends FileStorage {

  /**
   * The redis client.
   *
   * @var \Redis
   */
  private $redis;

  /**
   * Prefix for redis.
   *
   * @var string
   */
  protected $prefix;

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
    parent::__construct(config_get_config_directory(CONFIG_SYNC_DIRECTORY), $collection);
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return $this->redis->hExists($this->prefix . '.' . $this->collection, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return json_decode($this->redis->hGet($this->prefix . '.' . $this->collection, $name), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = [];
    foreach ($names as $name) {
      $value = json_decode($this->redis->hGet($this->prefix . '.' . $this->collection, $name), TRUE);
      if ($value) {
        $list[$name] = $value;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    $this->redis->hSet($this->prefix . '.' . $this->collection, $name, json_encode($data));
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    $this->redis->hDel($this->prefix . '.' . $this->collection, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $list = [];
    $values = $this->redis->hGetAll($this->prefix . '.' . $this->collection);
    foreach ($values as $key => $element) {
      $list[] = $key;
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    throw new \Exception();
  }

  /**
   * Setter for redis client.
   *
   * @param \Redis $redis
   *   Service for redis interaction.
   */
  public function setClient(\Redis $redis) {
    $this->redis = $redis;
  }

  /**
   * Set the prefix for the redis entry.
   *
   * @param string $prefix
   *   The prefix for redis hashmap.
   */
  public function setPrefix($prefix) {
    $this->prefix = $prefix;
  }

}
