<?php

namespace Drupal\alexanders;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\EntityPermissionProvider;

/**
 * Provides permissions for orders.
 */
class OrderPermissionProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);
    $permissions['view alexanders_order']['title'] = (string) t('View any Alexanders order');
    $permissions['view own alexanders_order'] = [
      'title' => (string) t('View own Alexanders orders'),
      'provider' => 'alexanders',
    ];

    return $permissions;
  }

}
