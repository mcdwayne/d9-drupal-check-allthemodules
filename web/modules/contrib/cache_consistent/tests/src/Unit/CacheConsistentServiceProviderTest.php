<?php

namespace Drupal\Tests\cache_consistent\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\cache_consistent\CacheConsistentServiceProvider;

/**
 * Tests the Cache Consistent service provider class.
 *
 * @group cache_consistent
 *
 * @covers \Drupal\cache_consistent\CacheConsistentServiceProvider
 * @covers \Drupal\cache_consistent\Cache\ListCacheConsistentBackendsPass
 */
class CacheConsistentServiceProviderTest extends UnitTestCase {

  /**
   * Test service provider.
   */
  public function testServiceProvider() {
    $container = new ContainerBuilder();

    // Test ::register.
    $service_provider = new CacheConsistentServiceProvider();
    $service_provider->register($container);

    $before_optimization_passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
    $compiler_pass = reset($before_optimization_passes);

    $this->assertInstanceOf('Drupal\cache_consistent\Cache\ListCacheConsistentBackendsPass', $compiler_pass, 'The ServiceProvider did not add compiler pass.');

    // Test ::process.
    $container->register('cache.test')->addTag('cache.consistent');
    $compiler_pass->process($container);

    $cache_default_consistent_backends = $container->getParameter('cache_default_consistent_backends');

    $this->assertSame(['cache.test' => TRUE], $cache_default_consistent_backends, 'Cache bins were not collected properly.');

    // Test ::alter.
    $container->register('cache_factory', '\Drupal\Tests\cache_consistent\OldService');
    $container->register('cache_tags.invalidator', '\Drupal\Tests\cache_consistent\OldService');
    $container->register('cache_consistent.factory', '\Drupal\Tests\cache_consistent\NewService');
    $container->register('cache_consistent.invalidator', '\Drupal\Tests\cache_consistent\NewService');
    $container->register('cache.backend.database');
    $container->register('cache.backend.memory');
    $container->register('cache.backend.null');
    $container->register('cache.backend.chainedfast');

    $service_provider->alter($container);

    $services = $container->findTaggedServiceIds('cache.consistent');
    $expected = [
      'cache.test',
    ];
    $this->assertSame($expected, array_keys($services), 'Services were not tagged properly.');

    $service = $container->get('cache_factory');
    $this->assertInstanceOf('\Drupal\Tests\cache_consistent\NewService', $service, 'Service was not properly replaced.');
    $service = $container->get('cache_tags.invalidator');
    $this->assertInstanceOf('\Drupal\Tests\cache_consistent\NewService', $service, 'Service was not properly replaced.');
  }

}
