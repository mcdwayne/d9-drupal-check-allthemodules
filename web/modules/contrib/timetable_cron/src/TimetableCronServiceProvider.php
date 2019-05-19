<?php

namespace Drupal\timetable_cron;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the cron manager service.
 */
class TimetableCronServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides cron class to use it on timetablecron.
    $definition = $container->getDefinition('cron');
    $definition->setClass('Drupal\timetable_cron\TimetableCron');
  }

}
