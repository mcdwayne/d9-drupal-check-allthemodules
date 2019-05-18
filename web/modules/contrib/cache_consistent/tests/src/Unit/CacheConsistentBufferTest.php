<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\cache_consistent\Cache\CacheConsistentScrubber;
use Drupal\cache_consistent\Cache\CacheConsistentScrubberManager;
use Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum;
use Drupal\Tests\cache_consistent\Mockers;
use Drupal\Tests\UnitTestCase;
use Drupal\cache_consistent\Cache\CacheConsistentBuffer;
use Drupal\cache_consistent\Cache\CacheConsistentBufferInterface;
use Drupal\Core\Cache\Cache;
use Drupal\transactionalphp\TransactionalPhp;
use Drupal\transactionalphp\TransactionalPhpEvent;
use Gielfeldt\TransactionalPHP\Indexer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Cache Consistent buffer cache backend.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentBuffer
 */
class CacheConsistentBufferTest extends UnitTestCase {

  use Mockers;

  /**
   * Helper method for getting a data provider.
   */
  protected function getProvider($test = TRUE) {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $container = $this->mockContainer();
    $backend = $test ? $container->get('cache.backend.test1') : $container->get('cache.backend.test2');
    $container->set('cache.test', $backend);
    $container->set('cache_consistent.scrubber.manager', new CacheConsistentScrubberManager());
    $php = new TransactionalPhp($connection);
    $php->setContainer($container);
    $indexer = new Indexer($php);
    $checksum_provider = new CacheConsistentTagsChecksum($php);
    $cache_buffer = new CacheConsistentBuffer('test', $backend, $indexer, $checksum_provider);
    $cache_buffer->setContainer($container);
    $php->startTransactionEvent(1);
    $cache_buffer->startTransactionEvent(1);
    return [$cache_buffer, $container];
  }

  /**
   * Data provider for cache buffer test.
   *
   * @return array
   *   Arguments for tests.
   */
  public function cacheBufferDataProvider() {
    return [
      $this->getProvider(TRUE),
      $this->getProvider(FALSE),
    ];
  }

  /**
   * Test cache get.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testGet(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $item = $cache_buffer->get('test1');

    // Test stored settings.
    $this->assertSame($item, FALSE, 'The correct cache item was not returned.');
  }

  /**
   * Test cache set.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testSet(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');

    // Test stored settings.
    $item = $cache_buffer->get('test1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');

    $cache_buffer->setMultiple([
      'test2' => ['data' => 'value2', 'expire' => Cache::PERMANENT],
      'test3' => ['data' => 'value3', 'expire' => Cache::PERMANENT],
    ]);

    // Test stored settings.
    $cids = ['test2', 'test3'];
    $items = $cache_buffer->getMultiple($cids);
    $this->assertInternalType('array', $items, 'The correct cache items were not returned.');
    $this->assertSame([], $cids, 'The correct cache item was not returned.');
    $this->assertTrue(isset($items['test2']), 'The correct cache item was not returned.');
    $this->assertTrue(isset($items['test3']), 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['test2'], 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['test3'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['test2']->data, 'The correct cache item was not returned.');
    $this->assertSame('value3', $items['test3']->data, 'The correct cache item was not returned.');
  }

  /**
   * Test cache get multiple.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testGetMultiple(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test2', 'value2');
    $cache_buffer->set('test3', 'value3', time() - 60);
    $cache_buffer->delete('test2');

    // Test stored settings.
    $cids = ['test1', 'test2', 'test3'];
    $items = $cache_buffer->getMultiple($cids);
    $this->assertInternalType('array', $items, 'The correct cache items were not returned.');
    $this->assertSame([], $cids, 'The correct cache ids were not returned.');
    $this->assertTrue(isset($items['test1']), 'The correct cache item was not returned.');
    $this->assertTrue(isset($items['test2']), 'The correct cache item was not returned.');
    $this->assertTrue(isset($items['test3']), 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['test1'], 'The correct cache item was not returned.');
    $this->assertFalse($items['test2'], 'The correct cache item was not returned.');
    $this->assertFalse($items['test3'], 'The correct cache item was not returned.');
  }

  /**
   * Test delete.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testDelete(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->deleteMultiple(['test1']);

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    $cache_buffer->set('test1', 'value1');

    // Test stored settings.
    $items = $cache_buffer->get('test1', FALSE);
    $this->assertInternalType('object', $items, 'The correct cache item was not returned.');
    $this->assertEquals('value1', $items->data, 'The correct cache item was not returned.');
  }

  /**
   * Test delete all.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testDeleteAll(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->deleteAll();

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test2'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test2'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test2'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test2'], 'The correct cache item was not returned.');

    $cache_buffer->set('test1', 'value1');

    // Test stored settings.
    $items = $cache_buffer->get('test1', FALSE);
    $this->assertInternalType('object', $items, 'The correct cache item was not returned.');
    $this->assertEquals('value1', $items->data, 'The correct cache item was not returned.');
  }

  /**
   * Test invalidate.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testInvalidate(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->invalidateMultiple(['test1']);

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

  }

  /**
   * Test invalidate all.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testInvalidateAll(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->invalidateAll();

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test2'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame(['test2'], $cids, 'The correct cache id was not returned.');
    $this->assertSame([], $items, 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test2'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test2'], 'The correct cache item was not returned.');

    $cache_buffer->set('test1', 'value1');
    $items = $cache_buffer->get('test1', FALSE);
    $this->assertInternalType('object', $items, 'The correct cache item was not returned.');
    $this->assertEquals('value1', $items->data, 'The correct cache item was not returned.');
  }

  /**
   * Test garbage collection.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testGarbageCollection(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');

    $cache_buffer->garbageCollection();

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['test1'], 'The correct cache item was not returned.');
  }

  /**
   * Test remove bin.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testRemoveBin(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $cache_buffer->set('test1', 'value1');
    $cache_buffer->removeBin();

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test1'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test1'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test2'];
    $items = $cache_buffer->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test2'], 'The correct cache item was not returned.');

    // Test stored settings.
    $cids = ['test2'];
    $items = $cache_buffer->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertSame(FALSE, $items['test2'], 'The correct cache item was not returned.');

    $cache_buffer->set('test1', 'value1');

    // Test stored settings.
    $items = $cache_buffer->get('test1', FALSE);
    $this->assertInternalType('object', $items, 'The correct cache item was not returned.');
    $this->assertEquals('value1', $items->data, 'The correct cache item was not returned.');
  }

  /**
   * Test scrubbing.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testScrub(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $container->get('cache_consistent.scrubber.manager')->addCacheScrubber(new CacheConsistentScrubber());

    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test2', 'value3');

    $indexer = $cache_buffer->getTransactionalPhpIndexer();
    $php = $indexer->getConnection();

    $operations = $indexer->getOperations();
    $event = new TransactionalPhpEvent($php, ['operations' => &$operations]);
    $cache_buffer->scrubOperations($event);

    $this->assertCount(2, $operations, 'Operations not correctly scrubbed.');
  }

  /**
   * Test scrubbing using an invalid event.
   *
   * @dataProvider cacheBufferDataProvider
   */
  public function testScrubInvalidEvent(CacheConsistentBufferInterface $cache_buffer, ContainerInterface $container) {
    $container->get('cache_consistent.scrubber.manager')->addCacheScrubber(new CacheConsistentScrubber());

    $cache_buffer->set('test1', 'value1');
    $cache_buffer->set('test1', 'value2');
    $cache_buffer->set('test2', 'value3');

    $indexer = $cache_buffer->getTransactionalPhpIndexer();
    $php = $indexer->getConnection();
    $invalid_php = new TransactionalPhp($php->getTrackedConnection());

    $operations = $indexer->getOperations();
    $event = new TransactionalPhpEvent($invalid_php, ['operations' => &$operations]);
    $cache_buffer->scrubOperations($event);

    $this->assertCount(3, $operations, 'Operations not correctly scrubbed.');
  }

}
