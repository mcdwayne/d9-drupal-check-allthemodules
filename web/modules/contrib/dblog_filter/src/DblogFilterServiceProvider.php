<?php

namespace Drupal\dblog_filter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class MyModuleServiceProvider.
 *
 * @package Drupal\mymodule
 */
class DblogFilterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('logger.dblog');
    $definition->setClass('Drupal\dblog_filter\Logger\DBLogFilter');
    $definition->setArguments(
      [
        new Reference('database'),
        new Reference('logger.log_message_parser'),
      ]
    );
  }

}
