<?php

namespace Drupal\image_field_permissions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class ImageFieldPermissionsServiceProvider.
 *
 * @package Drupal\image_field_permissions
 */
class ImageFieldPermissionsServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('plugin.field_permissions.types.manager')
      ->setClass('Drupal\image_field_permissions\ImageFieldPermissionsPermissionsManager');
  }

}
