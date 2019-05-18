<?php

namespace Drupal\cache_consistent;


use Drupal\cache_consistent\Cache\ListCacheConsistentBackendsPass;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Class CacheConsistentServiceProvider.
 *
 * @ingroup container
 */
class CacheConsistentServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Add the compiler pass that will process the tagged services.
    $container->addCompilerPass(new ListCacheConsistentBackendsPass());
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides cache_factory to let Cache Consistent take over.
    if ($container->hasDefinition('cache_factory') && $container->hasDefinition('cache_consistent.factory')) {
      $container->setAlias('cache_factory', 'cache_consistent.factory');
    }

    // Overrides cache_tags.invalidator to let Cache Consistent take over.
    if ($container->hasDefinition('cache_tags.invalidator') && $container->hasDefinition('cache_consistent.invalidator')) {
      $container->setAlias('cache_tags.invalidator', 'cache_consistent.invalidator');
    }
  }

}
