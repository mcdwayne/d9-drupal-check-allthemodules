<?php

namespace Drupal\Tests\cryptolog\Functional;

/**
 * Tests Cryptolog with the Memcache Storage contributed module.
 *
 * @group cryptolog
 * @requires module memcache_storage
 */
class CryptologMemcacheStorageTest extends CryptologMemcacheTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog', 'cryptolog', 'memcache_storage'];

}
