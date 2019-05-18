<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\HookalyzerServiceProvider.
 */

namespace Drupal\hookalyzer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Overriders certain core service implementations in order to track hook and
 * event activity.
 */
class HookalyzerServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {}

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // For now, we only operate during non-install - less complicated.
    if ($container->getParameter('kernel.environment') !== 'install') {
      $definition = $container->getDefinition('module_handler');
      $definition->setClass('Drupal\hookalyzer\ModuleHandler');
    }
  }
}