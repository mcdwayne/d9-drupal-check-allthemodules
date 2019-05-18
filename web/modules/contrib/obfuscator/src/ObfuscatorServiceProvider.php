<?php

namespace Drupal\obfuscator;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a Service Provider for the obfuscator module.
 */
class ObfuscatorServiceProvider extends ServiceProviderBase {

  /**
   * Removes the X-Generator HTTP Header to hide the current version of Drupal.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The ContainerBuilder.
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('response_generator_subscriber');
  }

}
