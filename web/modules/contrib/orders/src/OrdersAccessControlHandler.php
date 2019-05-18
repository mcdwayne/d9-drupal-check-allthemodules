<?php

namespace Drupal\orders;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Orders entity.
 *
 * @see \Drupal\orders\Entity\Orders.
 */
class OrdersAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\orders\Entity\OrdersInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished orders entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published orders entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit orders entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete orders entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add orders entities');
  }

}
