<?php

/**
 * @file
 * Contains \Drupal\renderviz\RendervizServiceProvider.
 */

namespace Drupal\renderviz;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service provider for the renderviz module.
 */
class RendervizServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('renderer')->setClass('Drupal\renderviz\Renderer');
  }

}
