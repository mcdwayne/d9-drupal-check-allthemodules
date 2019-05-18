<?php

namespace Drupal\purest;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the class for the menu link tree.
 */
class PurestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.active_trail');
    $definition->setClass('Drupal\purest\PurestMenuActiveTrail');
    $definition->addArgument(new Reference('router.request_context'));
    $definition->addArgument(new Reference('language_manager'));
  }

}
