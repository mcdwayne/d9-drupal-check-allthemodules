<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseServiceProvider.
 */

namespace Drupal\entity_base;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines services.
 */
class EntityBaseServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

    $container->register('access_check.entity_base_access', 'Drupal\entity_base\Access\EntityBaseAccessCheck')
      ->addArgument(new Reference(('entity.manager')))
      ->addTag('access_check', array('applies_to' => '_entity_base_access_check'));

    $container->register('access_check.entity_base_revision_access', 'Drupal\entity_base\Access\EntityBaseAccessCheck')
      ->addArgument(new Reference(('entity.manager')))
      ->addTag('access_check', array('applies_to' => '_entity_base_revision_access_check'));

  }

}
