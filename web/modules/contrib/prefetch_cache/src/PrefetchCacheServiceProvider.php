<?php

namespace Drupal\prefetch_cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\prefetch_cache\Render\Placeholder\PrefetchCacheStrategy;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers a decorator service for the BigPipe Placeholder Strategy.
 */
class PrefetchCacheServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Register a decorator service only in case big_pipe is enabled. Checking
    // for the existence of the BigPipe Placeholder Strategy service should be
    // equivalent to checking if the module is enabled, as the service is being
    // registered in big_pipe.services.yml and not through a service provider.
    $decorated_service_id = 'placeholder_strategy.big_pipe';
    if ($container->has($decorated_service_id)) {
      $container->register('placeholder_strategy.prefetch_cache', PrefetchCacheStrategy::class)
        ->setPublic(FALSE)
        ->setDecoratedService($decorated_service_id)
        ->addArgument(new Reference('placeholder_strategy.prefetch_cache.inner'))
        ->addArgument(new Reference('request_stack'));
    }
  }

}
