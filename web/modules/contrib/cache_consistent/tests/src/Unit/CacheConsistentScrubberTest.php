<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\cache_consistent\Cache\CacheConsistentScrubber;
use Drupal\cache_consistent\Cache\CacheConsistentScrubberManager;
use Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Tests\cache_consistent\Mockers;
use Drupal\Tests\UnitTestCase;
use Drupal\cache_consistent\Cache\CacheConsistentBuffer;
use Drupal\cache_consistent\Cache\CacheConsistentBufferInterface;
use Drupal\transactionalphp\TransactionalPhp;
use Gielfeldt\TransactionalPHP\Indexer;

/**
 * Tests the Cache Consistent buffer cache backend.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentScrubber
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentScrubberManager
 */
class CacheConsistentScrubberTest extends UnitTestCase {

  use Mockers;

  /**
   * Data provider for cache buffer test.
   *
   * @return array
   *   Arguments for tests.
   */
  public function cacheScrubberDataProvider() {
    $bin = 'test';
    $connection = $this->mockDatabaseConnection('default', 'default');
    $backend = new MemoryBackend($bin);
    $php = new TransactionalPhp($connection);
    $indexer = new Indexer($php);
    $checksum_provider = new CacheConsistentTagsChecksum($php);
    $cache_buffer = new CacheConsistentBuffer('test', $backend, $indexer, $checksum_provider);
    $cache_scrubber = new CacheConsistentScrubber();
    $php->startTransactionEvent(1);
    $cache_buffer->startTransactionEvent(1);
    $manager = new CacheConsistentScrubberManager();
    $manager->addCacheScrubber($cache_scrubber);
    return [[$cache_buffer, $manager]];
  }

  /**
   * Test setup.
   */
  public function testSetup() {
    $data = $this->cacheScrubberDataProvider();

    $this->assertCount(1, $data, 'Data provider did not return correct data.');
  }

  /**
   * Test superfluous sets.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberSuperfluousSet(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test1', 'value3');
    $cache_buffer->set('test1', 'value4');
    $cache_buffer->set('test1', 'value5');
    $cache_buffer->set('test2', 'value1');

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(2, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('set', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[0]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value5', $scrubbed_operations[0]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
    $this->assertEquals('value1', $scrubbed_operations[1]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

  /**
   * Test superfluous set multiple.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberSuperfluousSetMultiple(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test2', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test2', 'value2');
    $cache_buffer->setMultiple([
      'test1' => ['data' => 'value3'],
      'test3' => ['data' => 'value1'],
    ]);
    $cache_buffer->set('test1', 'value4');
    $cache_buffer->set('test2', 'value3');

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(3, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('setMultiple', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[2]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[2]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value4', $scrubbed_operations[1]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
    $this->assertEquals('value3', $scrubbed_operations[2]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

  /**
   * Test delete.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberDelete(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test1', 'value3');
    $cache_buffer->set('test1', 'value4');
    $cache_buffer->set('test1', 'value5');
    $cache_buffer->set('test2', 'value1');
    $cache_buffer->delete('test1');
    $cache_buffer->delete('test3');

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(3, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('set', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('delete', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('delete', $scrubbed_operations[2]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[0]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test3', $scrubbed_operations[2]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value1', $scrubbed_operations[0]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

  /**
   * Test delete multiple.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberDeleteMultiple(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test2', 'value2');
    $cache_buffer->set('test3', 'value3');
    $cache_buffer->set('test4', 'value4');
    $cache_buffer->delete('test1');
    $cache_buffer->deleteMultiple(['test2', 'test3']);
    $cache_buffer->delete('test2');

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(4, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('set', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('delete', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('deleteMultiple', $scrubbed_operations[2]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('delete', $scrubbed_operations[3]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test4', $scrubbed_operations[0]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[3]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value4', $scrubbed_operations[0]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

  /**
   * Test delete all.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberDeleteAll(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->invalidate('test2');
    $cache_buffer->delete('test3');
    $cache_buffer->invalidateAll();
    $cache_buffer->invalidateTags(['tag1']);
    $cache_buffer->deleteAll();

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(1, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('deleteAll', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
  }

  /**
   * Test invalidate.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberInvalidate(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test2', 'value3');
    $cache_buffer->set('test2', 'value4');
    $cache_buffer->invalidate('test1');

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(3, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('set', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('invalidate', $scrubbed_operations[2]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[0]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[2]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value2', $scrubbed_operations[0]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
    $this->assertEquals('value4', $scrubbed_operations[1]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

  /**
   * Test invalidate multiple.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberInvalidateMultiple(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test2', 'value3');
    $cache_buffer->set('test3', 'value4');
    $cache_buffer->set('test4', 'value5');
    $cache_buffer->invalidate('test1');
    $cache_buffer->invalidate('test2');
    $cache_buffer->invalidateMultiple(['test2', 'test3']);
    $cache_buffer->invalidate('test2');

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(7, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('set', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[2]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[3]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('invalidate', $scrubbed_operations[4]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('invalidateMultiple', $scrubbed_operations[5]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('invalidate', $scrubbed_operations[6]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[0]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test3', $scrubbed_operations[2]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test4', $scrubbed_operations[3]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[4]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[6]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value5', $scrubbed_operations[3]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

  /**
   * Test invalidate all.
   *
   * @dataProvider cacheScrubberDataProvider
   */
  public function testScrubberInvalidateAll(CacheConsistentBufferInterface $cache_buffer, CacheConsistentScrubberManager $manager) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test2', 'value3');
    $cache_buffer->set('test2', 'value4');
    $cache_buffer->invalidate('test1');
    $cache_buffer->invalidate('test2');
    $cache_buffer->invalidate('test3');
    $cache_buffer->invalidateAll();

    $operations = $cache_buffer->getTransactionalPhpIndexer()->getOperations();
    $scrubbed_operations = $manager->scrub($operations);

    $this->assertNotSame($operations, $scrubbed_operations, 'Items were not scrubbed correctly.');
    $this->assertCount(3, $scrubbed_operations, 'Items were not scrubbed correctly.');

    $scrubbed_operations = array_values($scrubbed_operations);
    $this->assertEquals('set', $scrubbed_operations[0]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('set', $scrubbed_operations[1]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('invalidateAll', $scrubbed_operations[2]->getMetadata('operation'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test1', $scrubbed_operations[0]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('test2', $scrubbed_operations[1]->getMetadata('cid'), 'Items were not scrubbed correctly.');
    $this->assertEquals('value2', $scrubbed_operations[0]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
    $this->assertEquals('value4', $scrubbed_operations[1]->getMetadata('data')->data, 'Items were not scrubbed correctly.');
  }

}
