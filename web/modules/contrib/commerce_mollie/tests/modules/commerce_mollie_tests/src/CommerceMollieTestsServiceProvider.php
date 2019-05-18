<?php

namespace Drupal\commerce_mollie_tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the mollie encapsulation service.
 */
class CommerceMollieTestsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('commerce_mollie.mollie.api');
    $definition->setClass('Drupal\commerce_mollie_tests\Services\MollieApiMock');
  }

}
