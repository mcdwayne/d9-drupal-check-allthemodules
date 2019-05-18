<?php

namespace Drupal\micro_node;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\micro_site\Routing\SiteRouteProvider;

/**
 * Overrides the cache_context.user.node_grants service.
 *
 * Because we want change the default max-age set by the service.
 */
class MicroNodeServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('cache_context.user.node_grants');
    $definition->setClass('Drupal\micro_node\Cache\MicroNodeAccessGrantsCacheContext');
  }

}
