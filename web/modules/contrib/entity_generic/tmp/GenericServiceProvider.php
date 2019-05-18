<?php

namespace Drupal\entity_generic;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines services.
 */
class GenericServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

    $container->register('access_check.generic_access', 'Drupal\entity_generic\Access\GenericAccessCheck')
      ->addArgument(new Reference(('entity.manager')))
      ->addTag('access_check', array('applies_to' => '_generic_access_check'));

    $container->register('access_check.generic_revision_access', 'Drupal\entity_generic\Access\GenericAccessCheck')
      ->addArgument(new Reference(('entity.manager')))
      ->addTag('access_check', array('applies_to' => '_generic_revision_access_check'));

  }

}
