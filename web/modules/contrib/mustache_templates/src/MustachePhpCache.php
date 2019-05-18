<?php

namespace Drupal\mustache;

use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * The Mustache PHP file cache.
 */
class MustachePhpCache extends \Mustache_Cache_AbstractCache {

  /**
   * The PhpStorage object used for storing the templates.
   *
   * @var \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected $storage;

  /**
   * The prefix to when generating cache IDs.
   *
   * @var string
   */
  protected $prefix;

  /**
   * MustachePhpCache constructor.
   *
   * @param string $prefix
   *   The prefix to use when generating the cache IDs.
   */
  public function __construct($prefix) {
    $this->prefix = $prefix;
    $this->storage = PhpStorageFactory::get('mustache');
  }

  /**
   * {@inheritdoc}
   */
  public function load($key) {
    $key = $this->prefix . $key;
    if (!$this->storage->exists($key)) {
      return FALSE;
    }
    $this->storage->load($key);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cache($key, $value) {
    $this->storage->save($this->prefix . $key, $value);
    // Ensure the cached instance is loaded for now.
    $this->load($key);
  }

}
