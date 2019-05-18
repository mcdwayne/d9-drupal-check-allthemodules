<?php

namespace Drupal\salsa_api_mock;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

class SalsaApiMockServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('salsa_api')) {
      // Override the class used for the salsa_api service.
      $definition = $container->getDefinition('salsa_api');
      $definition->setClass('Drupal\salsa_api_mock\SalsaApiMock');
    }
  }
}
