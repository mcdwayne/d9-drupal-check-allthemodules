<?php

namespace Drupal\supercache\Tests\Generic\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Drupal\Core\Site\Settings;

/**
 * Tests to make sure a cache tags checksum works
 * properly...
 */
abstract class CacheTagsChecksumTests extends KernelTestBase {

  /**
   * The cache factory.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  protected $backendFactory;

  /**
   * Tag invalidator.
   *
   * @var \Drupal\supercache\Cache\CacheTagsInvalidatorInterface
   */
  protected $tagInvalidator;

  /**
   * Tag checksum.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $tagChecksum;

  /**
   * Test that tag invalidations work.
   */
  public function testTagInvalidations() {

    // This test is designed for invalidators and checksums
    // that are the same object, because they contain internal
    // static caches that are meant to work together.
    if ($this->tagInvalidator !== $this->tagInvalidator) {
      $this->fail('Incorrect setup for this test.');
    }

    // Make sure we reset the tags...
    $this->tagInvalidator->resetTags();

    // Create cache entry in multiple bins.
    $tags = ['test_tag:1', 'test_tag:2', 'test_tag:3'];
    $bins = ['data', 'bootstrap', 'render'];
    foreach ($bins as $bin) {
      $bin = $this->backendFactory->get($bin);
      $bin->set('test', 'value', Cache::PERMANENT, $tags);
      $this->assertTrue($bin->get('test'), 'Cache item was set in bin.');
    }

    $checksum = $this->tagChecksum->getCurrentChecksum($tags);
    $this->tagInvalidator->invalidateTags(['test_tag:2']);

    // Total checksum should be increased by 1
    $this->assertEquals($this->tagChecksum->getCurrentChecksum($tags), $checksum + 1);
    $checksum = $this->tagChecksum->getCurrentChecksum($tags);

    // Total checksum should be increased by 3
    $this->tagInvalidator->invalidateTags($tags);
    $this->assertEquals($this->tagChecksum->getCurrentChecksum($tags), $checksum + 3);

    // Test that cache entry has been invalidated in multiple bins.
    foreach ($bins as $bin) {
      $bin = $this->backendFactory->get($bin);
      $this->assertFalse($bin->get('test'), 'Tag invalidation affected item in bin.');
    }

    // After reset the checksum should be 0
    $this->tagInvalidator->resetTags();
    $this->assertEquals($this->tagChecksum->getCurrentChecksum($tags), 0);

    // Make sure that items that do not have a tag, are not affected
    // by other tag invalidations
    $bin = $this->backendFactory->get($bins[0]);

    $bin->setMultiple(['test0' => ['data' => 'data0', 'tags' => [$tags[0], $tags[1]]]]);
    $bin->setMultiple(['test1' => ['data' => 'data1', 'tags' => [$tags[2]]]]);

    $this->tagInvalidator->invalidateTags([$tags[0]]);

    $this->assertFalse($bin->get('test0'), 'Tag invalidation affected item in bin.');
    $this->assertEquals('data1', $bin->get('test1')->data, 'Tag invalidation did not affect item in bin.');

    $this->tagInvalidator->invalidateTags([$tags[2]]);
    $this->assertFalse($bin->get('test1'), 'Tag invalidation affected item in bin.');

  }
}
