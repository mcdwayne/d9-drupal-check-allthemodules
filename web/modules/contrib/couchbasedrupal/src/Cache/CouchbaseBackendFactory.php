<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseBackendFactory.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\Core\Site\Settings;
use Drupal\couchbasedrupal\CouchbaseManager;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\couchbasedrupal\TranscoderPassThru;

class CouchbaseBackendFactory implements CacheFactoryInterface {

  /**
   * The site prefix string.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Couchbase manager.
   *
   * @var CouchbaseManager
   */
  protected $couchbaseManager;

  /**
   * Summary of $consistent
   * @var mixed
   */
  protected $consistent;


  /**
   * Constructs an CouchbaseBackendFactory object.
   *
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct(CouchbaseManager $manager, CacheTagsChecksumInterface $checksum_provider, $consistent = FALSE) {
    $this->couchbaseManager = $manager;
    $this->checksumProvider = $checksum_provider;
    // We need shorter and more readable prefixes.
    $this->sitePrefix = $manager->getSitePrefix();
    $this->consistent = $consistent;
  }


  /**
   * Gets CouchaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return CouchbaseBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    $bucket = $this->couchbaseManager->getBucketFromConfig('default', CouchbaseCacheTranscoder::class);
    $bucketPassthrough = $this->couchbaseManager->getBucketFromConfig('default', TranscoderPassThru::class);
    $backend = new CouchbaseBackend($this->sitePrefix . ':' . $bin . ':', $this->checksumProvider, $bucket, $bucketPassthrough);
    $backend->setConsistent($this->consistent);
    return $backend;
  }
}
