<?php

namespace Drupal\google_appliance_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a class for modifying the search service in tests.
 */
class GoogleApplianceTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('google_appliance.search')->setClass(TestSearch::class);
  }

}
