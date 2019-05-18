<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\cache_consistent\Cache\CacheConsistentBackend;
use Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum;
use Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\cache_consistent\Mockers;
use Drupal\Tests\UnitTestCase;
use Drupal\cache_consistent\Cache\CacheConsistentBuffer;
use Drupal\cache_consistent\Cache\CacheConsistentBufferInterface;
use Drupal\Core\Cache\Cache;
use Drupal\transactionalphp\TransactionalPhp;
use Gielfeldt\TransactionalPHP\Indexer;

/**
 * Tests the Cache Consistent module.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentBuffer
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentBackend
 * @covers \Drupal\cache_consistent\Cache\CacheBackendAwareTrait
 * @covers \Drupal\cache_consistent\Cache\CacheBufferAwareTrait
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator
 * @covers \Drupal\cache_consistent\Cache\CacheTagsChecksumAwareTrait
 * @covers \Drupal\cache_consistent\Cache\CacheTagsInvalidatorAwareTrait
 */
class CacheConsistentTest extends UnitTestCase {

  use Mockers;

  /**
   * Helper method for getting a data provider.
   */
  protected function getProvider($tx_point, $isolation_level = 1, $test = TRUE) {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $container = $this->mockContainer();
    $php = new TransactionalPhp($connection);
    $php->setContainer($container);
    $indexer = new Indexer($php);
    $checksum_provider = new CacheConsistentTagsChecksum($php);
    $checksum_provider->reset();
    $backend = $test ? $container->get('cache.backend.test1') : $container->get('cache.backend.test2');
    $buffer = new CacheConsistentBuffer('test', $backend, $indexer, $checksum_provider);
    $consistent = new CacheConsistentBackend($buffer, $checksum_provider, $isolation_level);
    $container->set('cache.test', $consistent);

    // Create a dummy container.
    $invalidator = new CacheConsistentTagsInvalidator(new Settings([]));
    $invalidator->setContainer($container);
    $invalidator->setTransactionalPhp($php);
    $invalidator->addInvalidator($container->get('cache.checksum.test'));
    $invalidator->addConsistentInvalidator($checksum_provider);

    return [$consistent, $buffer, $php, $invalidator, $tx_point];
  }

  /**
   * Get a single data provider.
   */
  public function singleDataProvider() {
    $data = [];
    $data[] = $this->getProvider(0, 1, TRUE);
    $data[] = $this->getProvider(0, 1, FALSE);
    return $data;
  }

  /**
   * Get a non-transactional and two transactional data providers.
   */
  public function simpleDataProvider() {
    $data = [];
    for ($tx_point = 0; $tx_point <= 2; $tx_point++) {
      $data[] = $this->getProvider($tx_point, 1, TRUE);
      $data[] = $this->getProvider($tx_point, 1, FALSE);
    }
    return $data;
  }

  /**
   * Get a non-transactional and 127 transactional data providers.
   */
  public function transactionDataProvider() {
    $data = [];
    for ($tx_point = 0; $tx_point <= 127; $tx_point++) {
      $data[] = $this->getProvider($tx_point, 1, TRUE);
      $data[] = $this->getProvider($tx_point, 1, FALSE);
    }
    return $data;
  }

  /**
   * Get two transactional data providers.
   */
  public function isolationDataProvider() {
    $data = [];
    $data[] = $this->getProvider(1, 2, TRUE);
    $data[] = $this->getProvider(3, 2, TRUE);
    $data[] = $this->getProvider(1, 2, FALSE);
    $data[] = $this->getProvider(3, 2, FALSE);
    return $data;
  }

  /**
   * Start a transaction.
   */
  protected function startTransaction($php, $buffer) {
    $php->startTransactionEvent(NULL);
    $buffer->startTransactionEvent($php->getDepth());
  }

  /**
   * Commit a transaction.
   */
  protected function commitTransaction($php, $buffer) {
    $php->commitTransactionEvent(NULL);
    $buffer->commitTransactionEvent($php->getDepth());
  }

  /**
   * Rollback a transaction.
   */
  protected function rollbackTransaction($php, $buffer) {
    $php->rollbackTransactionEvent(NULL);
    $buffer->rollbackTransactionEvent($php->getDepth());
  }

  /**
   * Commit remaining transactions.
   */
  public function closeTransactions($php, $buffer) {
    while ($php->getDepth()) {
      $this->commitTransaction($php, $buffer);
    }
  }

  /**
   * Test multiple scenarios.
   *
   * @dataProvider transactionDataProvider
   */
  public function testScenarios(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    // Set value.
    $backend->set('key1', 'value1');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    // Get value.
    $cids = ['key1'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');

    if ($tx_point & 4) {
      $this->startTransaction($php, $buffer);
    }
    // Set expired value.
    $backend->set('key2', 'value2', time() - 100);

    if ($tx_point & 8) {
      $this->startTransaction($php, $buffer);
    }

    // Get value.
    $cids = ['key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');

    if ($tx_point & 16) {
      $this->startTransaction($php, $buffer);
    }

    // Set tags value.
    $backend->set('key3', 'value3', Cache::PERMANENT, ['tag1', 'tag2']);

    if ($tx_point & 32) {
      $this->startTransaction($php, $buffer);
    }

    $invalidator->invalidateTags(['tag1']);

    if ($tx_point & 64) {
      $this->startTransaction($php, $buffer);
    }

    // Get value.
    $cids = ['key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key3'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key3'], 'The correct cache item was not returned.');
    $this->assertSame('value3', $items['key3']->data, 'The correct cache item was not returned.');

    $backend->invalidateAll();

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key3'], 'The correct cache item was not returned.');
    $this->assertSame('value3', $items['key3']->data, 'The correct cache item was not returned.');

    $backend->deleteAll();

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');
  }

  /**
   * Test get method.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testGet(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');
  }

  /**
   * Test the multiple methods.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testMultiple(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->setMultiple([
      'key1' => ['data' => 'value1'],
      'key2' => ['data' => 'value2'],
    ]);

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    $backend->invalidateMultiple(['key2']);

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    // Get values - allow invalid.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    $backend->deleteMultiple(['key1', 'key2']);

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get values.
    $cids = ['key1', 'key2', 'key3'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2', 'key3'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key3']), 'The correct cache item was not returned.');
  }

  /**
   * Test delete.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testDelete(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $backend->delete('key1');

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');
  }

  /**
   * Test invalidate.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testInvalidate(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $backend->invalidate('key1');

    // Get value.
    $cids = ['key1'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get value.
    $cids = ['key1'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], $cids, 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
  }

  /**
   * Test delete all.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testDeleteAll(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');
    $backend->set('key2', 'value2');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $backend->deleteAll();

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
  }

  /**
   * Test invalidate all.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testInvalidateAll(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');
    $backend->set('key2', 'value2');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $backend->invalidateAll();

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');
  }

  /**
   * Test remove bin.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testRemoveBin(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');
    $backend->set('key2', 'value2');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $backend->removeBin();

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame(['key1', 'key2'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertFalse(isset($items['key2']), 'The correct cache item was not returned.');
  }

  /**
   * Test tag invalidation.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testInvalidateTags(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1', Cache::PERMANENT, ['tag1', 'tag2']);
    $backend->set('key2', 'value2', Cache::PERMANENT, ['tag2', 'tag3']);

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $invalidator->invalidateTags(['tag1']);

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');

    $this->closeTransactions($php, $buffer);

    // Get values.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, FALSE);
    $this->assertSame(['key1'], array_values($cids), 'The correct cache id was not returned.');
    $this->assertFalse(isset($items['key1']), 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');

    // Get value - allow invalid.
    $cids = ['key1', 'key2'];
    $items = $backend->getMultiple($cids, TRUE);
    $this->assertSame([], array_values($cids), 'The correct cache id was not returned.');
    $this->assertInternalType('object', $items['key1'], 'The correct cache item was not returned.');
    $this->assertSame('value1', $items['key1']->data, 'The correct cache item was not returned.');
    $this->assertInternalType('object', $items['key2'], 'The correct cache item was not returned.');
    $this->assertSame('value2', $items['key2']->data, 'The correct cache item was not returned.');
  }

  /**
   * Test garbage collection.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider simpleDataProvider
   */
  public function testGarbageCollection(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->garbageCollection();

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $backend->garbageCollection();

    $this->assertTrue(TRUE, 'We cannot test this without mocking the entire backend.');

    $this->closeTransactions($php, $buffer);
  }

  /**
   * Test isolation level (disable cache set).
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider isolationDataProvider
   */
  public function testIsolationLevelSet(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->set('key1', 'value1');

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');

    if ($tx_point & 2) {
      $this->commitTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');

    if ($tx_point & 1) {
      $this->commitTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');
  }

  /**
   * Test isolation level (disable cache set).
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider isolationDataProvider
   */
  public function testIsolationLevelSetMultiple(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    if ($tx_point & 1) {
      $this->startTransaction($php, $buffer);
    }

    $backend->setMultiple([
      'key1' => ['data' => 'value1'],
    ]);

    if ($tx_point & 2) {
      $this->startTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');

    if ($tx_point & 2) {
      $this->commitTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');

    if ($tx_point & 1) {
      $this->commitTransaction($php, $buffer);
    }

    $item = $backend->get('key1');
    $this->assertFalse($item, 'The correct cache item was not returned.');
  }

  /**
   * Test transactions.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $buffer
   *   The cache buffer.
   * @param \Drupal\transactionalphp\TransactionalPhp $php
   *   The transactional php.
   * @param \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator $invalidator
   *   The invalidator.
   * @param int $tx_point
   *   The transaction point.
   *
   * @dataProvider singleDataProvider
   */
  public function testTransaction(CacheBackendInterface $backend, CacheConsistentBufferInterface $buffer, TransactionalPhp $php, CacheConsistentTagsInvalidator $invalidator, $tx_point) {
    $backend->set('key1', 'value0');

    $this->startTransaction($php, $buffer);

    $backend->set('key1', 'value1');

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');

    $this->startTransaction($php, $buffer);

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');

    $backend->set('key1', 'value2');

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value2', $item->data, 'The correct cache item was not returned.');

    $this->rollbackTransaction($php, $buffer);

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');

    $this->startTransaction($php, $buffer);

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value1', $item->data, 'The correct cache item was not returned.');

    $backend->set('key1', 'value3');

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value3', $item->data, 'The correct cache item was not returned.');

    $this->commitTransaction($php, $buffer);

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value3', $item->data, 'The correct cache item was not returned.');

    $this->rollbackTransaction($php, $buffer);

    $item = $backend->get('key1');
    $this->assertInternalType('object', $item, 'The correct cache item was not returned.');
    $this->assertSame('value0', $item->data, 'The correct cache item was not returned.');
  }

}
