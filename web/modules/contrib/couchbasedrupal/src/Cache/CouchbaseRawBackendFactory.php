<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseRawBackendFactory.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\couchbasedrupal\CouchbaseManager;

use Drupal\Core\Site\Settings;

use Drupal\supercache\Cache\CacheRawFactoryInterface;

class CouchbaseRawBackendFactory implements CacheRawFactoryInterface {

  /**
   * Couchbase manager service.
   * 
   * @var CouchbaseManager
   */
  protected $couchbaseManager;

  /**
   * The cache tags checksum provider.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * Constructs the WincacheRawBackendFactory
   * 
   * @param string $root
   *   The site's root.
   * @param string $site_path 
   *   The site's path.
   */
  function __construct(CouchbaseManager $manager) {
    $this->couchbaseManager = $manager;
    // We need shorter and more readable prefixes.
    $this->sitePrefix = $manager->getSitePrefix();
  }

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return CouchbaseRawBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {
    $bucket = $this->couchbaseManager->getBucketFromConfig('default', CouchbaseCacheTranscoder::class);
    return new CouchbaseRawBackend($this->sitePrefix . ':' . $bin . ':', $bucket);
  }

}
