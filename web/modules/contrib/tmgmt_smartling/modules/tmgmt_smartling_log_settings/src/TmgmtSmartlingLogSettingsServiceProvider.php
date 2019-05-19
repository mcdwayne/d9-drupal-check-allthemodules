<?php

namespace Drupal\tmgmt_smartling_log_settings;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\tmgmt_smartling_log_settings\Logger\LoggerChannelFactory;

/**
 * Overrides the logger.factory service to enable record filtering.
 */
class TmgmtSmartlingLogSettingsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Alter logger factory in order to instantiate
    // Drupal\tmgmt_smartling_log_settings\Logger\LoggerChannel.
    $container->getDefinition('logger.factory')
      ->setClass(LoggerChannelFactory::class);
  }

}
