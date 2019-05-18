<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum;
use Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator;
use Drupal\Core\Site\Settings;
use Drupal\Tests\cache_consistent\Mockers;
use Drupal\Tests\UnitTestCase;
use Drupal\transactionalphp\TransactionalPhp;

/**
 * Tests the Cache Consistent tags invalidator.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentTagsInvalidator
 * @covers \Drupal\cache_consistent\Cache\CacheTagsInvalidatorAwareTrait
 */
class CacheConsistentTagsInvalidatorTest extends UnitTestCase {

  use Mockers;

  /**
   * Helper method for getting a data provider.
   */
  protected function getProvider($test = TRUE) {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $container = $this->mockContainer();
    $container->set('cache.test', $test ? $container->get('cache.backend.test1') : $container->get('cache.backend.test2'));
    $php = new TransactionalPhp($connection);
    $php->setContainer($container);
    $checksum_provider = new CacheConsistentTagsChecksum($php);

    $invalidator = new CacheConsistentTagsInvalidator(new Settings([]));
    $invalidator->setContainer($container);
    $invalidator->setTransactionalPhp($php);
    $invalidator->addInvalidator($container->get('cache.checksum.test'));
    return [$invalidator, $checksum_provider, $php];
  }

  /**
   * Data provider for cache tags invalidator test.
   *
   * @return array
   *   Arguments for tests.
   */
  public function tagsInvalidatorProvider() {
    return [
      $this->getProvider(TRUE),
      $this->getProvider(FALSE),
    ];
  }

  /**
   * Test invalidate tags.
   *
   * @dataProvider tagsInvalidatorProvider
   */
  public function testTagInvalidation($invalidator, $checksum_provider, $php) {
    $invalidator->addConsistentInvalidator($checksum_provider);
    $tags = ['tag1', 'tag2'];
    $checksum_before = $checksum_provider->getCurrentChecksum($tags);
    $invalidator->invalidateTags($tags);
    $checksum_after = $checksum_provider->getCurrentChecksum($tags);

    $this->assertEquals($checksum_before, $checksum_after, 'Checksum was not calculated properly.');
    $valid = $checksum_provider->isValid($checksum_after, $tags);
    $this->assertTrue($valid, 'Checksum was not calculated properly.');
  }

  /**
   * Transactionally test invalidate tags.
   *
   * @dataProvider tagsInvalidatorProvider
   */
  public function testTransactionalTagInvalidation($invalidator, $checksum_provider, $php) {
    $invalidator->addConsistentInvalidator($checksum_provider);
    $php->startTransactionEvent(1);

    $tags = ['tag1', 'tag2'];
    $checksum_before = $checksum_provider->getCurrentChecksum($tags);
    $invalidator->invalidateTags($tags);
    $checksum_after = $checksum_provider->getCurrentChecksum($tags);

    $this->assertNotEquals($checksum_before, $checksum_after, 'Checksum was not calculated properly.');
    $valid = $checksum_provider->isValid($checksum_after, $tags);
    $this->assertTrue($valid, 'Checksum was not calculated properly.');

    $php->commitTransactionEvent(0);

    $checksum_after = $checksum_provider->getCurrentChecksum($tags);

    $this->assertEquals($checksum_before, $checksum_after, 'Checksum was not calculated properly.');
  }

}
