<?php

namespace Drupal\Tests\guzzle_cache\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\guzzle_cache\DrupalGuzzleCache;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\CacheEntry;

/**
 * Tests Guzzle cache integration.
 *
 * @group guzzle_cache
 * @coversDefaultClass \Drupal\guzzle_cache\DrupalGuzzleCache
 */
class DrupalGuzzleCacheTest extends UnitTestCase {

  /**
   * Tests deleting a cache item.
   *
   * @covers ::delete
   */
  public function testDelete() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject $backend */
    $backend = $this->createMock(CacheBackendInterface::class);
    $cache = new DrupalGuzzleCache($backend);

    $backend->expects($this->once())->method('delete')
      ->with('guzzle:key');

    $this->assertTrue($cache->delete('key'));
  }

  /**
   * Tests fetching a cache item.
   *
   * @covers ::fetch
   */
  public function testFetch() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject $backend */
    $backend = $this->createMock(CacheBackendInterface::class);
    $cache = new DrupalGuzzleCache($backend);

    $request = new Request('GET', 'http://example.com');
    $response = new Response();
    $time = new \DateTime();
    $entry = new CacheEntry($request, $response, $time);
    $item = new \stdClass();
    $item->data = $entry;

    $backend->expects($this->once())->method('get')
      ->with('guzzle:key')
      ->willReturn($item);

    $this->assertEquals($entry, $cache->fetch('key'));
  }

  /**
   * Tests saving a cache item.
   *
   * @covers ::save
   */
  public function testSave() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject $backend */
    $backend = $this->createMock(CacheBackendInterface::class);
    $cache = new DrupalGuzzleCache($backend);
    $request = new Request('GET', 'http://example.com');
    $response = new Response();
    $time = new \DateTime();
    $entry = new CacheEntry($request, $response, $time);

    $backend->expects($this->once())->method('set')
      ->with('guzzle:key', $entry, $entry->getStaleAt()->getTimestamp(), []);

    $this->assertTrue($cache->save('key', $entry));
  }

  /**
   * Tests the default prefix.
   *
   * @covers ::prefix
   */
  public function testPrefix() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $backend */
    $backend = $this->createMock(CacheBackendInterface::class);
    $cache = new DrupalGuzzleCache($backend);
    $key = $this->getRandomGenerator()->name();
    $this->assertEquals('guzzle:' . $key, $cache->prefix($key));
  }

  /**
   * Tests setting a custom prefix.
   *
   * @covers ::__construct
   * @covers ::setPrefix
   */
  public function testCustomPrefix() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $backend */
    $backend = $this->createMock(CacheBackendInterface::class);
    $prefix = $this->getRandomGenerator()->name(191);
    $cache = new DrupalGuzzleCache($backend, $prefix);
    $key = $this->getRandomGenerator()->name();
    $this->assertEquals($prefix . $key, $cache->prefix($key));
  }

  /**
   * Tests setting an invalid prefix.
   *
   * @covers ::setPrefix
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The cache key prefix cannot be longer than 191 characters.
   */
  public function testInvalidPrefix() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $backend */
    $backend = $this->createMock(CacheBackendInterface::class);
    $prefix = $this->getRandomGenerator()->name(192);
    new DrupalGuzzleCache($backend, $prefix);
  }

}
