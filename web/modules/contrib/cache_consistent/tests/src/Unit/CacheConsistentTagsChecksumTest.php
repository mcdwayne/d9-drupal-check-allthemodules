<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum;
use Drupal\Tests\cache_consistent\Mockers;
use Drupal\Tests\UnitTestCase;
use Drupal\transactionalphp\TransactionalPhp;

/**
 * Tests the Cache Consistent tags checksum provider.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum
 */
class CacheConsistentTagsChecksumTest extends UnitTestCase {

  use Mockers;

  /**
   * Data provider for cache tags checksum tests.
   *
   * @return array
   *   Arguments for tests.
   */
  public function tagsChecksumDataProvider() {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $container = $this->mockContainer();
    $php = new TransactionalPhp($connection);
    $php->setContainer($container);
    $checksum_provider = new CacheConsistentTagsChecksum($php);
    return [
      [$checksum_provider, $php],
    ];
  }

  /**
   * Test invalidate tags.
   *
   * @dataProvider tagsChecksumDataProvider
   */
  public function testTagInvalidation($checksum_provider, $php) {
    $tags = ['tag1', 'tag2'];
    $checksum_before = $checksum_provider->getCurrentChecksum($tags);
    $checksum_provider->invalidateTags($tags);
    $checksum_after = $checksum_provider->getCurrentChecksum($tags);

    $this->assertEquals($checksum_before, $checksum_after, 'Checksum was not calculated properly.');
    $valid = $checksum_provider->isValid($checksum_after, $tags);
    $this->assertTrue($valid, 'Checksum was not calculated properly.');
  }

  /**
   * Transactionally test invalidate tags.
   *
   * @dataProvider tagsChecksumDataProvider
   */
  public function testTransactionalTagInvalidation($checksum_provider, $php) {
    $php->startTransactionEvent(1);

    $tags = ['tag1', 'tag2'];
    $checksum_before = $checksum_provider->getCurrentChecksum($tags);
    $checksum_provider->invalidateTags($tags);
    $checksum_after = $checksum_provider->getCurrentChecksum($tags);

    $this->assertNotEquals($checksum_before, $checksum_after, 'Checksum was not calculated properly.');
    $valid = $checksum_provider->isValid($checksum_after, $tags);
    $this->assertTrue($valid, 'Checksum was not calculated properly.');

    $php->commitTransactionEvent(0);

    $checksum_after = $checksum_provider->getCurrentChecksum($tags);

    $this->assertEquals($checksum_before, $checksum_after, 'Checksum was not calculated properly.');
  }

}
