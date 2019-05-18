<?php

namespace Drupal\calendar_systems;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Automatically picked up by DI.
 */
class CalendarSystemsServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container) {
    $formatter = $container->getDefinition('date.formatter');
    $formatter->setClass(CalendarSystemsFormatter::class);
  }

}
