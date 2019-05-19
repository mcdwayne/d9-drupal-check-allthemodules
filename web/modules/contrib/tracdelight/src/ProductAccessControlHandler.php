<?php

/**
 * @file
 * Contains Drupal\tracdelight\ProductAccessControlHandler.
 */

namespace Drupal\tracdelight;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Product entity.
 *
 * @see \Drupal\tracdelight\Entity\Product.
 */
class ProductAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view product entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit product entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete product entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add product entities');
  }

}
