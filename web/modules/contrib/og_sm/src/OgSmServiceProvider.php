<?php

namespace Drupal\og_sm;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the admin negotiator service.
 */
class OgSmServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('theme.negotiator.admin_theme');
    $definition->setClass('Drupal\og_sm\Theme\AdminNegotiator');
    $definition->addArgument(new Reference('og_sm.site_manager'));
    $definition->addArgument(new Reference('og.access'));
    $container->setDefinition('theme.negotiator.admin_theme', $definition);
  }

}
