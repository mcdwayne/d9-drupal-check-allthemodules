<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseRawBackend.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\supercache\Cache\CacheRawBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\couchbasedrupal\CouchbaseBucket as Bucket;

/**
 * Container to deal with couchbase binary
 * storage messing up with arrays/object
 */
class CouchbaseRawBackendItemHolder {

  protected $data;

  public function __construct($data) {
    $this->data = serialize($data);
  }

  public function get() {
    return unserialize($this->data);
  }
}
