<?php

/**
 * @file
 * Contains \Drupal\config_db\ConfigDbServiceProvider.
 */

namespace Drupal\config_db;

use Symfony\Component\DependencyInjection\Reference;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Overrides CMI storage.
 */
class ConfigDbServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {}

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->getParameter('kernel.environment') !== 'install') {
      $definition = $container->getDefinition('config.storage');
      $definition->setClass('Drupal\config_db\Config\DbStorage');
      $definition->setArguments(array(new Reference('database')));
    }
  }
}

