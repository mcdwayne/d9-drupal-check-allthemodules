<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseFileCacheBackend.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\FileCacheBackendInterface;

/**
 * Couchbase backend for the file cache.
 * 
 * TODO: Not sure if it is realy apropriate to implement this...
 */
class CouchbaseFileCacheBackend implements FileCacheBackendInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    throw new \Exception("Not implemented");
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
    throw new \Exception("Not implemented");
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    throw new \Exception("Not implemented");
  }

}
