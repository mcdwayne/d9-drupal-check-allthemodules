<?php

namespace Drupal\entity_access;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\entity_access\Entity\EntityAccessCheck;

/**
 * Class EntityAccessServiceProvider.
 */
class EntityAccessServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('access_check.entity')
      ->setClass(EntityAccessCheck::class);
  }

}
