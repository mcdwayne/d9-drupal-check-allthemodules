<?php

namespace Drupal\rokka;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the image factory service.
 */
class RokkaServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('image.factory');
    $definition->setClass('Drupal\rokka\RokkaImageFactory');
  }
}