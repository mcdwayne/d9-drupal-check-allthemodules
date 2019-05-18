<?php

namespace Drupal\advertising_products;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Advertising Product entity.
 *
 * @see \Drupal\advertising_products\Entity\AdvertisingProduct.
 */
class AdvertisingProductAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\advertising_products\AdvertisingProductInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished advertising product entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published advertising product entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit advertising product entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete advertising product entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add advertising product entities');
  }

}
