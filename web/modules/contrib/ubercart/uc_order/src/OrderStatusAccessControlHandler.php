<?php

namespace Drupal\uc_order;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for Ubercart order statuses.
 */
class OrderStatusAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $order_status, $operation, AccountInterface $account) {
    /** @var \Drupal\uc_order\OrderStatusInterface $order_status */

    switch ($operation) {
      case 'view':
        // User can view order statuses, who has permission to view orders or
        // has permission to administer the order workflow.
        if ($account->hasPermission('view all orders') ||
            $account->hasPermission('view own orders') ||
            $account->hasPermission('administer order workflow')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        else {
          return AccessResult::forbidden()->cachePerPermissions();
        }

      case 'update':
        // User can update an order status, if has permission to administer
        // order workflow.
        return AccessResult::allowedIfHasPermission($account, 'administer order workflow')->cachePerPermissions()->cachePerUser();

      case 'delete':
        // User can delete an order status, if has permission to administer
        // order workflow.
        return AccessResult::allowedIfHasPermission($account, 'administer order workflow')->cachePerPermissions()->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer order workflow')->cachePerPermissions();
  }

}
