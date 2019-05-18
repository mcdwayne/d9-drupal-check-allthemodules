<?php

namespace Drupal\products;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Products entity.
 *
 * @see \Drupal\products\Entity\Products.
 */
class ProductsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\products\Entity\ProductsInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished products entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published products entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit products entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete products entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add products entities');
  }

}
