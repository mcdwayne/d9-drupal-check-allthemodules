<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\GridBuilderBundle.
 */

namespace Drupal\gridbuilder;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * GridBuilder dependency injection container.
 */
class GridBuilderBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the GridBuilderManager class with the dependency injection container.
    $container->register('plugin.manager.gridbuilder', 'Drupal\gridbuilder\Plugin\Type\GridBuilderManager');
  }
}
