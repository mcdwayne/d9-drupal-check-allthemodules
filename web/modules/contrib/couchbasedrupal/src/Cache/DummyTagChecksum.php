<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\DummyTagChecksum.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Stub checksum interface, for when you need to use a cache backend
 * without tag invalidation support.
 */
class DummyTagChecksum implements CacheTagsChecksumInterface {

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) { return 0; }

  /**
   * {@inheritdoc}
   */
  public function isValid($checksum, array $tags) { return TRUE; }

  /**
   * {@inheritdoc}
   */
  public function reset() { return; }
}