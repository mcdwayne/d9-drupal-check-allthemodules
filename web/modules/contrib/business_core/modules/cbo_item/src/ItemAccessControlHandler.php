<?php

namespace Drupal\cbo_item;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the item entity type.
 *
 * @see \Drupal\cbo_item\Entity\Item
 */
class ItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access item');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit ' . $entity->bundle() . ' item', 'administer items'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete ' . $entity->bundle() . ' item', 'administer items'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create ' . $entity_bundle . ' item', 'administer items'], 'OR');
  }

}
