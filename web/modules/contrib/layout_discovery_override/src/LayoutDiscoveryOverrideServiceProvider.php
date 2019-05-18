<?php

namespace Drupal\layout_discovery_override;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Override layout services from Drupal core.
 */
class LayoutDiscoveryOverrideServiceProvider extends ServiceProviderBase implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('plugin.manager.core.layout');
    // Override the core layout plugin manager.
    $definition->setClass('Drupal\layout_discovery_override\Layout\LayoutPluginManager');
  }

}
