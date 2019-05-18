<?php

namespace Drupal\bynder_test_module;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for the bynder_test_module.
 */
class BynderTestModuleServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Modifies the Bynder api service to use our test class.
    $container->getDefinition('bynder_api')
      ->setClass('Drupal\bynder_test_module\BynderApiTest')
      ->setArguments([
        new Reference('config.factory'),
        new Reference('logger.factory'),
        new Reference('session'),
        new Reference('state'),
        new Reference('cache.default'),
        new Reference('datetime.time'),
      ]);
  }

}
