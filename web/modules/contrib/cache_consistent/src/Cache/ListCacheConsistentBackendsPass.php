<?php

namespace Drupal\cache_consistent\Cache;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class ListCacheConsistentBackendsPass.
 *
 * Adds cache consistent bins parameter to the container.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
class ListCacheConsistentBackendsPass implements CompilerPassInterface {

  /**
   * Collects the consistent cache backends into a container parameter.
   *
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $cache_default_consistent_backends = array();
    foreach ($container->findTaggedServiceIds('cache.consistent') as $id => $attributes) {
      $cache_default_consistent_backends[$id] = TRUE;
    }
    $container->setParameter('cache_default_consistent_backends', $cache_default_consistent_backends);
  }

}
