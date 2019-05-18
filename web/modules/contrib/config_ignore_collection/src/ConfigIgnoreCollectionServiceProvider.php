<?php

namespace Drupal\config_ignore_collection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies external services.
 */
class ConfigIgnoreCollectionServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('config_split.cli');
    $definition->setClass(ConfigIgnoreCollectionSplitCliService::class);
  }

}
