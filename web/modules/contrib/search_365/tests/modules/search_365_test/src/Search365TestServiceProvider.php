<?php

namespace Drupal\search_365_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a class for modifying the search service in tests.
 */
class Search365TestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('search_365.search')->setClass(MockSearchClient::class);
  }

}
