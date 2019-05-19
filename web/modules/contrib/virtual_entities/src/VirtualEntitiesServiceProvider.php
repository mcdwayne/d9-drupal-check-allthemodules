<?php

namespace Drupal\virtual_entities;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class VirtualEntitiesServiceProvider.
 *
 * @package Drupal\virtual_entities
 * @see https://www.drupal.org/docs/8/api/services-and-dependency-injection/altering-existing-services-providing-dynamic-services
 */
class VirtualEntitiesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $serializations = [
      'serialization.json',
      'serialization.phpserialize',
      'serialization.yaml',
    ];

    foreach ($serializations as $serialization) {
      $definition = $container->getDefinition($serialization);
      $definition->addTag('virtual_entity_storage_client_decoder');
    }
  }

}
