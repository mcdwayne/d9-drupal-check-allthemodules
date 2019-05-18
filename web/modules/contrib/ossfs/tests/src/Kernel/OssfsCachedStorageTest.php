<?php

namespace Drupal\Tests\ossfs\Kernel;

use Drupal\Core\Cache\NullBackend;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ossfs\OssfsCachedStorage;
use Drupal\ossfs\OssfsStorageInterface;

/**
 * @group ossfs
 */
class OssfsCachedStorageTest extends KernelTestBase {

  /**
   * Modules to installs.
   *
   * @var array
   */
  protected static $modules = [
    'ossfs',
  ];

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();
    $this->cache = $this->container->get('cache.ossfs');
  }

  /**
   * Test exists().
   */
  public function testExists() {
    $uri = 'oss://abc.jpg';

    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->exactly(2))
      ->method('exists')
      ->with($uri)
      ->will($this->returnValue(FALSE));

    $cached_storage = new OssfsCachedStorage($storage, $this->cache);

    $this->assertSame(FALSE, $cached_storage->exists($uri));
    $this->assertSame(FALSE, $cached_storage->exists($uri));
  }

  /**
   * Test read() cache hit.
   */
  public function testReadCacheHit() {
    $uri = 'oss://abc.jpg';
    $response = [
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
    ];

    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->once())
      ->method('read')
      ->with($uri)
      ->will($this->returnValue($response));

    $cached_storage = new OssfsCachedStorage($storage, $this->cache);

    $this->assertEquals($response, $cached_storage->read($uri));
    $this->assertEquals($response, $cached_storage->read($uri));
  }

  /**
   * Test read() also caches missing information.
   */
  public function testReadCacheMiss() {
    $uri = 'oss://abc.jpg';

    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->once())
      ->method('read')
      ->with($uri)
      ->will($this->returnValue(FALSE));

    $cached_storage = new OssfsCachedStorage($storage, $this->cache);

    $this->assertSame(FALSE, $cached_storage->read($uri));
    $this->assertSame(FALSE, $cached_storage->read($uri));
  }

  /**
   * Tests write().
   */
  public function testWrite() {
    $uri = 'oss://abc.jpg';
    $data = [
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
    ];

    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->once())
      ->method('write')
      ->with($uri, $data)
      ->will($this->returnValue(TRUE));
    $storage->expects($this->never())
      ->method('read')
      ->with($uri);

    $cached_storage = new OssfsCachedStorage($storage, $this->cache);

    $this->assertSame(TRUE, $cached_storage->write($uri, $data));
    $this->assertEquals($data, $cached_storage->read($uri));
  }

  /**
   * Tests delete().
   */
  public function testDelete() {
    $uri = 'oss://abc.jpg';
    $data = [
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
    ];

    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->once())
      ->method('write')
      ->with($uri, $data)
      ->will($this->returnValue(TRUE));
    $storage->expects($this->once())
      ->method('delete')
      ->with($uri)
      ->will($this->returnValue(TRUE));
    $storage->expects($this->once())
      ->method('read')
      ->with($uri)
      ->will($this->returnValue(FALSE));

    $cached_storage = new OssfsCachedStorage($storage, $this->cache);

    $this->assertSame(TRUE, $cached_storage->write($uri, $data));
    $this->assertSame(TRUE, $cached_storage->delete($uri));
    $this->assertSame(FALSE, $cached_storage->read($uri));
  }

  /**
   * Tests rename().
   */
  public function testRename() {
    $uri = 'oss://abc.jpg';
    $new_uri = 'oss://def.jpg';
    $data = [
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
    ];

    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->once())
      ->method('write')
      ->with($uri, $data)
      ->will($this->returnValue(TRUE));
    $storage->expects($this->once())
      ->method('rename')
      ->with($uri, $new_uri)
      ->will($this->returnValue(TRUE));
    $storage->expects($this->exactly(2))
      ->method('read')
      ->will($this->returnValueMap([
        [$uri, FALSE],
        [$new_uri, ['uri' => 'oss://def.jpg', 'type' => 'file']],
      ]));

    $cached_storage = new OssfsCachedStorage($storage, $this->cache);

    $this->assertSame(TRUE, $cached_storage->write($uri, $data));
    $this->assertSame(TRUE, $cached_storage->rename($uri, $new_uri));
    $this->assertSame(FALSE, $cached_storage->read($uri));
    $this->assertEquals(['uri' => 'oss://def.jpg', 'type' => 'file'], $cached_storage->read($new_uri));
  }

  /**
   * Test listAll() static cache.
   */
  public function testListAllStaticCache() {
    $response = [
      'oss://0/a.jpg',
      'oss://0/b.jpg'
    ];

    $prefix = 'oss://0/';
    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->once())
      ->method('listAll')
      ->with($prefix)
      ->will($this->returnValue($response));

    $cache = new NullBackend(__FUNCTION__);
    $cached_storage = new OssfsCachedStorage($storage, $cache);

    $this->assertEquals($response, $cached_storage->listAll($prefix));
    $this->assertEquals($response, $cached_storage->listAll($prefix));

    $prefix = '';
    $storage = $this->getMock(OssfsStorageInterface::class);
    $storage->expects($this->exactly(2))
      ->method('listAll')
      ->with($prefix)
      ->will($this->returnValue($response));

    $cache = new NullBackend(__FUNCTION__);
    $cached_storage = new OssfsCachedStorage($storage, $cache);

    $this->assertEquals($response, $cached_storage->listAll($prefix));
    $this->assertEquals($response, $cached_storage->listAll($prefix));
  }

}
