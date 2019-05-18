<?php

namespace Drupal\Tests\php_ffmpeg\Unit;

use \Drupal\php_ffmpeg\PHPFFMpegCache;
use \Drupal\Core\Cache\MemoryBackend;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for PHPFFMpegCache.
 *
 * @group php_ffmp
 */
class PHPFFMpegCacheTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Cache\MemoryBackend
   */
  protected $backend;

  /**
   * @var string
   */
  protected $prefix;

  /**
   * @var \Drupal\php_ffmpeg\PHPFFMpegCache
   */
  protected $cache;


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['php_ffmpeg'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->backend = new MemoryBackend('php_ffmpeg');
    $this->prefix = $this->randomMachineName();
    $this->cache = new PHPFFMpegCache($this->backend, $this->prefix);
  }

  public function testFetch() {
    $cid = $this->randomMachineName();
    $value = $this->randomMachineName();
    $this->backend->set("{$this->prefix}:{$cid}", $value);
    self::assertEquals($this->cache->fetch($cid)->data, $value, 'PHPFFMpeg::get() should return the value stored in the backend when it exists.');
    $this->assertFalse($this->cache->fetch($this->randomMachineName()), 'PHPFFMpeg::get() should return FALSE when no value exist in the backend.');
  }

  public function testContains() {
    $cid = $this->randomMachineName();
    $value = $this->randomMachineName();
    $this->backend->set("{$this->prefix}:{$cid}", $value);
    self::assertSame($this->cache->contains($cid), TRUE, 'PHPFFMpeg::contains() should return TRUE when a value exists in the backend.');
    self::assertSame($this->cache->contains($this->randomMachineName()), FALSE, 'PHPFFMpeg::contains() should return FALSE when no value exist in the backend.');
  }

  public function testSave() {
    $cid = $this->randomMachineName();
    $value = $this->randomMachineName();
    $this->cache->save($cid, $value);
    self::assertEquals($this->backend->get("{$this->prefix}:{$cid}")->data, $value, 'PHPFFMpeg::save() should set the value in the backend.');
  }

  public function testDelete() {
    $cid = $this->randomMachineName();
    $value = $this->randomMachineName();
    $this->backend->set("{$this->prefix}:{$cid}", $value);
    $this->cache->delete($cid);
    self::assertSame($this->backend->get("{$this->prefix}:{$cid}"), FALSE, 'PHPFFMpeg::delete() should clear the value in the backend.');
  }

  public function testGetStats() {
    self::assertSame($this->cache->getStats(), NULL, 'PHPFFMpeg::getStats() should return NULL.');
  }

}
