<?php

namespace Drupal\cache_alter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the Page Cache service.
 */
class CacheAlterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $page_cache = $container->getDefinition('http_middleware.page_cache');
    $page_cache->setClass('Drupal\cache_alter\StackMiddleware\CacheAlter');

    $dynamic_cache = $container->getDefinition('dynamic_page_cache_subscriber');
    $dynamic_cache->setClass('Drupal\cache_alter\EventSubscriber\DynamicCacheAlter');
  }

}
