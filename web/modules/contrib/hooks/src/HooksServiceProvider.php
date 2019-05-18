<?php

/**
 * @file
 * Contains \Drupal\hooks\HooksServiceProvider.
 */

namespace Drupal\hooks;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class HooksServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides module_handler class so we can fire events for all alter hooks.
    $definition = $container->getDefinition('module_handler');
    $definition->setClass('Drupal\hooks\ModuleHandler');
    $definition->addArgument(new Reference('event_dispatcher'));
  }

}
