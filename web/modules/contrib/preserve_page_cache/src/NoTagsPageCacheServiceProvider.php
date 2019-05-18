<?php

namespace Drupal\preserve_page_cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\preserve_page_cache\StackMiddleware\NoTagsPageCache;

/**
 * A service provider for registering the NoTagsPageCache.
 */
class NoTagsPageCacheServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('http_middleware.page_cache')
      ->setClass(NoTagsPageCache::class);
  }

}
