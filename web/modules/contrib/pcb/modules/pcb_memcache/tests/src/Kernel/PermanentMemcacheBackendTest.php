<?php

namespace Drupal\Tests\pcb_memcache\Kernel;

use Drupal\KernelTests\Core\Cache\GenericCacheBackendUnitTestBase;

/**
 * Tests the PermanentMemcacheBackendTest.
 *
 * @requires module memcache
 *
 * @group pcb_memcache
 */
class PermanentMemcacheBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'pcb',
    'pcb_memcache',
    'memcache',
  ];

  /**
   * Creates a new instance of PermanentMemcacheBackend.
   *
   * @return \Drupal\pcb_memcache\Cache\PermanentMemcacheBackend
   *   A new PermanentMemcacheBackend cache backend.
   */
  protected function createCacheBackend($bin) {
    $cache = \Drupal::service('cache.backend.permanent_memcache')->get($bin);
    return $cache;
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testInvalidate() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testInvalidateAll() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testInvalidateTags() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testRemoveBin() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testValueTypeIsKept() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testSetMultiple() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testDelete() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testDeleteAll() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testDeleteMultiple() {
  }

  /**
   * We don't want to run this test, module memcache has to deal with.
   */
  public function testGetMultiple() {
  }

  /**
   * Testing the basic goals of the permanent memcache cache.
   */
  public function testSetGet() {
    $backend = $this->getCacheBackend();
    $cid = 'test';
    $cache_value = 'This does not matter.';

    // Be sure that our cache key is empty.
    $this->assertSame(FALSE, $backend->get($cid), "Backend does not contain data for the used cache id.");

    // Initialize the cache value.
    $backend->set($cid, $cache_value);
    $cached = $backend->get($cid);
    $this->assertEquals($cache_value, $cached->data, 'Backend returned the proper value before the normal deleting process.');

    // This is the original cache deleteAll method, so we don't want to delete
    // anything at this moment.
    $backend->deleteAll();
    $cached = $backend->get($cid);
    $this->assertFalse(!is_object($cached), 'Backend did not provide result after the normal deleting process.');
    $this->assertEquals($cache_value, $cached->data, 'Backend returned the proper value after the normal deleting process.');

    // Now flush the permanent cache!
    $backend->deleteAllPermanent();
    $cached = $backend->get($cid);
    $this->assertFalse(is_object($cached), 'Backend returned result after the permanent cache was deleted.');
  }

}
