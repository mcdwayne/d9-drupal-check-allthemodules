<?php

namespace Drupal\commerce_payplug_tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
* Modifies the PayPlug encapsulation service.
*/
class CommercePayplugTestsServiceProvider extends ServiceProviderBase {

  /**
  * {@inheritdoc}
  */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('commerce_payplug.payplug.service');
    $definition->setClass('Drupal\commerce_payplug_tests\Services\PayPlugServiceTest');
  }
}