<?php

namespace Drupal\multiline_config;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Multiline config customizations service provider implementation.
 */
class MultilineConfigServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /* @deprecated in Drupal 8.0.x and will be removed before 9.0.0. */
    // Change to config.storage.sync when config.storage.staging is removed.
    if ($config_storage = $container->getDefinition('config.storage.staging')) {
      $config_storage->setClass('Drupal\multiline_config\MultilineConfigFileStorage');
      $config_storage->setFactory('Drupal\multiline_config\MultilineConfigFileStorageFactory::getSync');
    }
  }

}
