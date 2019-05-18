<?php

namespace Drupal\commerce_order;

use Drupal\entity\EntityPermissionProvider;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides permissions for orders.
 */
class OrderPermissionProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);
    // Orders don't implement EntityOwnerInterface, so they don't get
    // own/any permissions generated by default.
    $permissions['view commerce_order']['title'] = (string) t('View any order');
    $permissions['view own commerce_order'] = [
      'title' => (string) t('View own orders'),
      'provider' => 'commerce_order',
    ];

    return $permissions;
  }

}
