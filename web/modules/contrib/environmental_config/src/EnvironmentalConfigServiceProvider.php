<?php

namespace Drupal\environmental_config;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines the environmental_config service provider(s).
 */
class EnvironmentalConfigServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides config.storage.staging to inject our service.
    $definition = $container->getDefinition('config.storage.staging');
    $definition->setFactory('Drupal\environmental_config\Config\FileStorageFactory::getSync');
  }

}
