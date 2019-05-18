<?php

namespace Drupal\image_field_permissions;

use Drupal\field_permissions\Plugin\FieldPermissionType\Manager;

/**
 * Class ImageFieldPermissionsPermissionsManager.
 *
 * @package Drupal\image_field_permissions
 */
class ImageFieldPermissionsPermissionsManager extends Manager {
  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    if (isset($definitions['custom'])) {
      $class = 'Drupal\image_field_permissions\Plugin\FieldPermissionType\AdvancedCustomAccess';
      $definitions['custom']['class'] = $class;
    }
    return $definitions;
  }
}
