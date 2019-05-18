<?php

namespace Drupal\commerce_inventory\Entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Inventory Location entity.
 *
 * @see \Drupal\commerce_inventory\Entity\InventoryLocation.
 */
class InventoryLocationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface $entity */

    // Admin permission check.
    if ($account->hasPermission($entity->getEntityType()->getAdminPermission())) {
      return AccessResult::allowed()->addCacheContexts(['user.permissions']);
    }

    // Check location owner.
    $owner = ($entity->getOwnerId() == $account->id());

    switch ($operation) {
      case 'view':
        if ($owner && $account->hasPermission('view own commerce inventory location')) {
          return AccessResult::allowed()->addCacheContexts(['user.permissions'])->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'view any commerce inventory location');

      case 'inventory':
        if ($owner && $account->hasPermission('access own commerce inventory location inventory')) {
          return AccessResult::allowed()->addCacheContexts(['user.permissions'])->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'access any commerce inventory location inventory');

      case 'inventory_modify':
        if ($owner && $account->hasPermission('modify own commerce inventory location inventory')) {
          return AccessResult::allowed()->addCacheContexts(['user.permissions'])->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'modify any commerce inventory location inventory');

      case 'update':
        if ($owner && $account->hasPermission('edit own commerce inventory location')) {
          return AccessResult::allowed()->addCacheContexts(['user.permissions'])->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'edit any commerce inventory location');

      case 'delete':
        if ($owner && $account->hasPermission('delete own commerce inventory location')) {
          return AccessResult::allowed()->addCacheContexts(['user.permissions'])->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'delete any commerce inventory location');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add commerce inventory location');
  }

}
