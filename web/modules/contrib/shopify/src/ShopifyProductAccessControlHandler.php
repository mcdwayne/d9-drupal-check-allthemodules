<?php

namespace Drupal\shopify;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Shopify product entity.
 *
 * @see \Drupal\shopify\Entity\ShopifyProduct.
 */
class ShopifyProductAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view shopify product entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit shopify product entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete shopify product entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add shopify product entities');
  }

}
