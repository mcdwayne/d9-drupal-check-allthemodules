<?php

namespace Drupal\supercache\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\CacheTagsChecksumTests as TagsChecksumTests;

use Drupal\couchbasedrupal\Cache\CouchbaseRawBackendFactory;
use Drupal\couchbasedrupal\CouchbaseManager;
use Drupal\wincachedrupal\Cache\WincacheRawBackendFactory;

use Drupal\supercache\Cache\ApcuRawBackendFactory;
use Drupal\supercache\Cache\DatabaseRawBackendFactory;
use Drupal\supercache\Cache\CacheCacheTagsChecksum;
use Drupal\supercache\Cache\ChainedFastRawBackendFactory;

use Drupal\supercache\Cache\CacheServicesTrait;

use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\DatabaseBackendFactory;

/**
 * Test this on top of different combinations of cache backends.
 */
class CacheCacheTagsChecksumTests extends TagsChecksumTests {

  use CacheServicesTrait;

  /**
   * List of cache backends to perform the tests on.
   *
   * @var string[]
   */
  protected $backends;

  public function setUp() {
    parent::setUp();
    $this->backends = $this->populateRawCacheServices();
  }

  public function testTagInvalidations() {

    foreach ($this->backends as $backend) {

      /** @var \Drupal\supercache\Cache\CacheRawFactoryInterface */
      $factory = $this->container->get($backend);;

      // Invalidator and provider on top of the cache.
      $provider = new CacheCacheTagsChecksum($factory);
      $this->tagInvalidator = $provider;
      $this->tagChecksum = $provider;

      // The backend factory will be the same for all
      $connection = \Drupal\Core\Database\Database::getConnection();
      $this->backendFactory = new DatabaseBackendFactory($connection, $this->tagChecksum);

      // Call parent tests.
      parent::testTagInvalidations();
    }
  }

  public function tearDown() {

  }
}
