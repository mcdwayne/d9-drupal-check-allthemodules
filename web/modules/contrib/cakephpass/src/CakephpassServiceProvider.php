<?php

namespace Drupal\cakephpass;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the password service.
 */
class CakephpassServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides password class to support CakePhp migrated hashes.
    $definition = $container->getDefinition('password');
    $definition->setClass('Drupal\cakephpass\Password\CakePhPassword');
  }

}
