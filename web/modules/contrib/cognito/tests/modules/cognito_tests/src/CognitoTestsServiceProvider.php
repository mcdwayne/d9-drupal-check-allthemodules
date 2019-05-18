<?php

namespace Drupal\cognito_tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overwrite the cognito service with our own.
 */
class CognitoTestsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['cognito'])) {
      $container->getDefinition('cognito.aws')
        ->setClass(NullCognito::class)
        ->setFactory(NULL)
        ->setArguments([]);
    }
  }

}
