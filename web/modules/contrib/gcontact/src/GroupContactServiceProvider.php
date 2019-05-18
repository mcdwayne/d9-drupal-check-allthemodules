<?php

/**
 * @file
 * Contains \Drupal\gcontact\GroupContactServiceProvider.
 */

namespace Drupal\gcontact;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider implementation for Group Contact to override
 * access_check.contact_personal.
 *
 * @ingroup container
 */
class GroupContactServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the access_check.contact_personal class with a new class.
    $definition = $container->getDefinition('access_check.contact_personal');
    $definition->setClass('Drupal\gcontact\Access\GroupContactPageAccess');
  }

}

