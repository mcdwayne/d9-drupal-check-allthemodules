<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\cache_consistent\Cache\CacheConsistentTagsChecksum;
use Drupal\Core\Cache\MemoryBackendFactory;
use Drupal\Tests\cache_consistent\Mockers;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Site\Settings;
use Drupal\cache_consistent\Cache\CacheConsistentFactory;
use Drupal\cache_consistent\Cache\CacheConsistentBufferFactory;
use Drupal\transactionalphp\TransactionalPhp;

/**
 * Tests the Cache Consistent factory classes.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentFactory
 * @covers \Drupal\cache_consistent\Cache\CacheConsistentBufferFactory
 * @covers \Drupal\cache_consistent\Cache\CacheTagsChecksumAwareTrait
 */
class CacheConsistentFactoryTest extends UnitTestCase {

  use Mockers;

  /**
   * Test factory classes.
   *
   * @dataProvider factorySettingsProvider
   */
  public function testFactory($bin, $config, $default_bin_backends, $default_consistent_backends, $expected_backend, $has_buffer = TRUE) {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $php = new TransactionalPhp($connection);
    $checksum_provider = new CacheConsistentTagsChecksum($php);
    $container = $this->mockContainer();

    $settings = new Settings($config);
    $factory = new CacheConsistentFactory($settings, $default_bin_backends, $default_consistent_backends);
    $factory->setContainer($container);
    $factory->setChecksumProvider($checksum_provider);

    $backend_factory = new MemoryBackendFactory();

    $buffer_factory = new CacheConsistentBufferFactory();
    $buffer_factory->setContainer($container);
    $buffer_factory->setTransactionalPhp($php);
    $buffer_factory->setChecksumProvider($checksum_provider);

    $container->set('cache.backend.database', $backend_factory);
    $container->set('cache_consistent.buffer_factory', $has_buffer ? $buffer_factory : NULL);

    $cache = $factory->get($bin);
    $this->assertInstanceOf($expected_backend, $cache, 'The correct cache backend was not returned.');
  }

  /**
   * Data provider for factory tests.
   *
   * @return array
   *   Data for factory tests.
   */
  public function factorySettingsProvider() {
    $bin = 'test';

    $default_bins = ['test' => 'cache.backend.database'];
    $default_backends1 = ['cache.backend.database' => FALSE];
    $default_backends2 = ['cache.backend.database' => TRUE];

    $config1 = ['cache' => ['bins' => [$bin => 'cache.backend.database']]];
    $config2 = ['cache' => ['default' => 'cache.backend.database']];
    $config3 = ['cache' => []];
    $config4 = ['cache' => ['consistent' => TRUE]];
    $config5 = ['cache' => ['consistent_backends' => ['cache.backend.database' => TRUE]]];

    $consistent_backend = 'Drupal\cache_consistent\Cache\CacheConsistentBackend';
    $memory_backend = 'Drupal\Core\Cache\MemoryBackend';
    return [
      [$bin, $config1, [], [], $memory_backend, FALSE],
      [$bin, $config1, [], [], $consistent_backend],
      [$bin, $config2, [], [], $consistent_backend],
      [$bin, $config3, [], [], $consistent_backend],
      [$bin, $config3, $default_bins, [], $consistent_backend],
      [$bin, $config3, [], $default_backends1, $consistent_backend],
      [$bin, $config3, [], $default_backends2, $memory_backend],
      [$bin, $config4, [], [], $memory_backend],
      [$bin, $config5, [], [], $memory_backend],
    ];
  }

}
