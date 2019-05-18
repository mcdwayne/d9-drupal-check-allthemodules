<?php

namespace Drupal\maintenance_exempt;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class MaintenanceExemptServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('maintenance_mode');
    $definition->setClass('Drupal\maintenance_exempt\MaintenanceModeExempt');
  }
}
