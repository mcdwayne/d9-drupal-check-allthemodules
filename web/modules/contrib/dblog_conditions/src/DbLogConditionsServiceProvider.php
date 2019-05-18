<?php

namespace Drupal\dblog_conditions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;


class DbLogConditionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /** @var \Symfony\Component\DependencyInjection\Definition $dblog_logger */
    $dblog_logger = $container->getDefinition('logger.dblog');
    /** @var \Symfony\Component\DependencyInjection\Definition $dblog_conditions_logger */
    $dblog_conditions_logger = $container->getDefinition('logger.dblog_conditions');

    // replace DbLog by DbLogConditions by looking up the service definitions
    $dblog_logger->setClass($dblog_conditions_logger->getClass());
    $dblog_logger->setArguments($dblog_conditions_logger->getArguments());
  }
}