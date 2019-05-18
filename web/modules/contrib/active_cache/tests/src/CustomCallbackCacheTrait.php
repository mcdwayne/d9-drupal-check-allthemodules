<?php

namespace Drupal\Tests\active_cache;


use Drupal\active_cache_test\Plugin\ActiveCache\CustomCallbackTest;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CustomCallbackCacheTrait {

  /**
   * @param callable $callback
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param string $id
   * @param array $definition
   * @return \Drupal\active_cache\Plugin\ActiveCacheInterface
   */
  protected function createActiveCache(callable $callback, ContainerInterface $container, $id = 'custom_callback_test', $definition = []) {
    $definition += [
      'id' => $id,
      'label' => $id,
      'cache_id' => $id,
      'cache_tags' => [$id],
      'cache_bin' => 'default',
      'max_age' => Cache::PERMANENT
    ];
    $configuration = [
      'data_callback' => $callback
    ];

    return CustomCallbackTest::create($container, $configuration, $id, $definition);
  }

}