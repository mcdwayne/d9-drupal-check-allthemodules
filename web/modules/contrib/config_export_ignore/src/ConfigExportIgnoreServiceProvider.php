<?php

namespace Drupal\config_export_ignore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the config split service to prevent exporting specified items.
 */
class ConfigExportIgnoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides ConfigSplitService class to test domain language negotiation.
    $definition = $container->getDefinition('config_split.cli');
    $definition->setClass('Drupal\config_export_ignore\ConfigExportIgnoreConfigSplitService');
  }

}
