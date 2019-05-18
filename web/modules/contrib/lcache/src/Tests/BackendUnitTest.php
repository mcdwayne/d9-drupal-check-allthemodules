<?php

/**
 * @file
 * Contains \Drupal\lcache\Tests\BackendUnitTest.
 */

namespace Drupal\lcache\Tests;

use Drupal\lcache\BackendFactory;
use Drupal\system\Tests\Cache\GenericCacheBackendUnitTestBase;
use Drupal\Core\Cache\Cache;

/**
 * Tests the LCache Backend.
 *
 * @group lcache
 */
class BackendUnitTest extends GenericCacheBackendUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'lcache'];

  /**
   * Creates a new instance of an LCache Backend.
   *
   * @return \Drupal\lcache\Backend
   *   A new LCache Backend object.
   */
  protected function createCacheBackend($bin) {
    $factory = new BackendFactory($this->container->get('database'));
    return $factory->get($bin);
  }

  // This portion of the class contains tests that were copied from Core and
  // slightly modified to accommodate bugs.
  // @codingStandardsIgnoreStart
  /**
   * Tests the get and set methods of Drupal\Core\Cache\CacheBackendInterface.
   */
  public function testSetGet() {
    $backend = $this->getCacheBackend();

    $this->assertIdentical(FALSE, $backend->get('test1'), "Backend does not contain data for cache id test1.");
    $with_backslash = array('foo' => '\Drupal\foo\Bar');
    $backend->set('test1', $with_backslash);
    $cached = $backend->get('test1');
    $this->assert(is_object($cached), "Backend returned an object for cache id test1.");
    $this->assertIdentical($with_backslash, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    // We need to round because microtime may be rounded up in the backend.
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, Cache::PERMANENT, 'Expire time is correct.');

    $this->assertIdentical(FALSE, $backend->get('test2'), "Backend does not contain data for cache id test2.");
    $backend->set('test2', array('value' => 3), REQUEST_TIME + 3);
    $cached = $backend->get('test2');
    $this->assert(is_object($cached), "Backend returned an object for cache id test2.");
    $this->assertIdentical(array('value' => 3), $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, REQUEST_TIME + 3, 'Expire time is correct.');

    $backend->set('test3', 'foobar', REQUEST_TIME - 3);
    $this->assertFalse($backend->get('test3'), 'Invalid item not returned.');
    $cached = $backend->get('test3', TRUE);

    // @todo, this assertion is present in the parent class, currently fails.
    // LCache the treats invalidations the same as deletions.
    // https://github.com/lcache/lcache/issues/41
    // $this->assert(is_object($cached), 'Backend returned an object for cache id test3.');
    // $this->assertFalse($cached->valid, 'Item is marked as valid.');
    //$this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    //$this->assertEqual($cached->expire, REQUEST_TIME - 3, 'Expire time is correct.');

    $this->assertIdentical(FALSE, $backend->get('test4'), "Backend does not contain data for cache id test4.");
    $with_eof = array('foo' => "\nEOF\ndata");
    $backend->set('test4', $with_eof);
    $cached = $backend->get('test4');
    $this->assert(is_object($cached), "Backend returned an object for cache id test4.");
    $this->assertIdentical($with_eof, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, Cache::PERMANENT, 'Expire time is correct.');

    $this->assertIdentical(FALSE, $backend->get('test5'), "Backend does not contain data for cache id test5.");
    $with_eof_and_semicolon = array('foo' => "\nEOF;\ndata");
    $backend->set('test5', $with_eof_and_semicolon);
    $cached = $backend->get('test5');
    $this->assert(is_object($cached), "Backend returned an object for cache id test5.");
    $this->assertIdentical($with_eof_and_semicolon, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, Cache::PERMANENT, 'Expire time is correct.');

    $with_variable = array('foo' => '$bar');
    $backend->set('test6', $with_variable);
    $cached = $backend->get('test6');
    $this->assert(is_object($cached), "Backend returned an object for cache id test6.");
    $this->assertIdentical($with_variable, $cached->data);

    // Make sure that a cached object is not affected by changing the original.
    $data = new \stdClass();
    $data->value = 1;
    $data->obj = new \stdClass();
    $data->obj->value = 2;
    $backend->set('test7', $data);
    $expected_data = clone $data;
    // Add a property to the original. It should not appear in the cached data.
    $data->this_should_not_be_in_the_cache = TRUE;
    $cached = $backend->get('test7');
    $this->assert(is_object($cached), "Backend returned an object for cache id test7.");
    $this->assertEqual($expected_data, $cached->data);
    $this->assertFalse(isset($cached->data->this_should_not_be_in_the_cache));
    // Add a property to the cache data. It should not appear when we fetch
    // the data from cache again.
    $cached->data->this_should_not_be_in_the_cache = TRUE;
    $fresh_cached = $backend->get('test7');
    $this->assertFalse(isset($fresh_cached->data->this_should_not_be_in_the_cache));

    // Check with a long key.
    $cid = str_repeat('a', 300);
    $backend->set($cid, 'test');
    $this->assertEqual('test', $backend->get($cid)->data);

    // Check that the cache key is case sensitive.
    $backend->set('TEST8', 'value');
    $this->assertEqual('value', $backend->get('TEST8')->data);

    // @todo, this assertion is commented out until an upstream issue is resolved.
    // https://github.com/lcache/lcache/issues/42
    // $this->assertFalse($backend->get('test8'), print_r($backend->get('test8'), TRUE));

    // Calling ::set() with invalid cache tags. This should fail an assertion.
    try {
      $backend->set('assertion_test', 'value', Cache::PERMANENT, ['node' => [3, 5, 7]]);
      $this->fail('::set() was called with invalid cache tags, runtime assertion did not fail.');
    }
    catch (\AssertionError $e) {
      $this->pass('::set() was called with invalid cache tags, runtime assertion failed.');
    }
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function testInvalidateAll() {
    $backend_a = $this->getCacheBackend();
    $backend_b = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend_a->set('test1', 1, Cache::PERMANENT);
    $backend_a->set('test2', 3, time() + 1000);
    $backend_b->set('test3', 4, Cache::PERMANENT);

    $backend_a->invalidateAll();

    $this->assertFalse($backend_a->get('test1'), 'First key has been invalidated.');
    $this->assertFalse($backend_a->get('test2'), 'Second key has been invalidated.');
    $this->assertTrue($backend_b->get('test3'), 'Item in other bin is preserved.');

    // @todo, this assertion is present in the parent class, currently fails.
    // LCache the treats invalidations the same as deletions.
    // https://github.com/lcache/lcache/issues/41
    // $this->assertTrue($backend_a->get('test1', TRUE), 'First key has not been deleted.');
    // $this->assertTrue($backend_a->get('test2', TRUE), 'Second key has not been deleted.');
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  function testInvalidateTags() {
    $backend = $this->getCacheBackend();

    // Create two cache entries with the same tag and tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Two cache items were created.');

    // Invalidate test_tag of value 1. This should invalidate both entries.
    Cache::invalidateTags(array('test_tag:2'));
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two cache items invalidated after invalidating a cache tag.');

    // @todo, this assertion is present in the parent class, currently fails.
    // LCache the treats invalidations the same as deletions.
    // https://github.com/lcache/lcache/issues/41
    //$this->assertTrue($backend->get('test_cid_invalidate1', TRUE) && $backend->get('test_cid_invalidate2', TRUE), 'Cache items not deleted after invalidating a cache tag.');

    // Create two cache entries with the same tag and an array tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Two cache items were created.');

    // Invalidate test_tag of value 1. This should invalidate both entries.
    Cache::invalidateTags(array('test_tag:1'));
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two caches removed after invalidating a cache tag.');

    // @todo, this assertion is present in the parent class, currently fails.
    // LCache the treats invalidations the same as deletions.
    // https://github.com/lcache/lcache/issues/41
    //$this->assertTrue($backend->get('test_cid_invalidate1', TRUE) && $backend->get('test_cid_invalidate2', TRUE), 'Cache items not deleted after invalidating a cache tag.');

    // Create three cache entries with a mix of tags and tag values.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $backend->set('test_cid_invalidate3', $this->defaultValue, Cache::PERMANENT, array('test_tag_foo:3'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2') && $backend->get('test_cid_invalidate3'), 'Three cached items were created.');
    Cache::invalidateTags(array('test_tag_foo:3'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Cache items not matching the tag were not invalidated.');
    $this->assertFalse($backend->get('test_cid_invalidated3'), 'Cached item matching the tag was removed.');

    // Create cache entry in multiple bins. Two cache entries
    // (test_cid_invalidate1 and test_cid_invalidate2) still exist from previous
    // tests.
    $tags = array('test_tag:1', 'test_tag:2', 'test_tag:3');
    $bins = array('path', 'bootstrap', 'page');
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->set('test', $this->defaultValue, Cache::PERMANENT, $tags);
      $this->assertTrue($this->getCacheBackend($bin)->get('test'), 'Cache item was set in bin.');
    }

    Cache::invalidateTags(array('test_tag:2'));

    // Test that the cache entry has been invalidated in multiple bins.
    foreach ($bins as $bin) {
      $this->assertFalse($this->getCacheBackend($bin)->get('test'), 'Tag invalidation affected item in bin.');
    }
    // Test that the cache entry with a matching tag has been invalidated.
    $this->assertFalse($this->getCacheBackend($bin)->get('test_cid_invalidate2'), 'Cache items matching tag were invalidated.');

    // @todo, this assertion is present in the parent class, currently fails.
    // LCache the treats invalidations the same as deletions.
    // https://github.com/lcache/lcache/issues/41
    // Test that the cache entry with without a matching tag still exists.
    // $this->assertTrue($this->getCacheBackend($bin)->get('test_cid_invalidate1'), 'Cache items not matching tag were not invalidated.');
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidate() and
   * Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  public function testInvalidate() {
    $backend = $this->getCacheBackend();
    $backend->set('test1', 1);
    $backend->set('test2', 2);
    $backend->set('test3', 2);
    $backend->set('test4', 2);

    $reference = array('test1', 'test2', 'test3', 'test4');

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    $this->assertEqual(count($ret), 4, 'Four items returned.');

    $backend->invalidate('test1');
    $backend->invalidateMultiple(array('test2', 'test3'));

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    $this->assertEqual(count($ret), 1, 'Only one item element returned.');

    $cids = $reference;
    $ret = $backend->getMultiple($cids, TRUE);

    // @todo, this assertion is present in the parent class, currently fails.
    // LCache the treats invalidations the same as deletions.
    // https://github.com/lcache/lcache/issues/41
    // $this->assertEqual(count($ret), 4, 'Four items returned.');
    // Calling invalidateMultiple() with an empty array should not cause an
    // error.
    $this->assertFalse($backend->invalidateMultiple(array()));
  }
  // @codingStandardsIgnoreEnd

  /**
   * Test \Drupal\lcache\Backend::normalizeCids().
   */
  public function testSetGetLong() {

    $backend = $this->getCacheBackend();

    // Set up a cache ID that is not ASCII and longer than 255 characters so we
    // can test cache ID normalization.
    $cid_long = str_repeat('愛€', 1000);
    $cached_value_long = $this->randomMachineName();
    $backend->set($cid_long, $cached_value_long);
    $this->assertIdentical($cached_value_long, $backend->get($cid_long)->data, "Backend contains the correct value for long, non-ASCII cache id.");

    $cid_long = str_repeat('abcdefghijk', 100);
    $cached_value_long = $this->randomMachineName();
    $backend->set($cid_long, $cached_value_long);
    $this->assertIdentical($cached_value_long, $backend->get($cid_long)->data, "Backend contains the correct value for long, non-ASCII cache id.");

    $cid_short = '愛1€';
    $cached_value_short = $this->randomMachineName();
    $backend->set($cid_short, $cached_value_short);
    $this->assertIdentical($cached_value_short, $backend->get($cid_short)->data, "Backend contains the correct value for short, non-ASCII cache id.");
  }
}
