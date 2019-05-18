<?php

namespace Drupal\affiliates_connect;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Affiliates Product entity.
 *
 * @see \Drupal\affiliates_connect\Entity\AffiliatesProduct.
 */
class AffiliatesProductAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\affiliates_connect\Entity\AffiliatesProductInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished affiliates product entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published affiliates product entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit affiliates product entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete affiliates product entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add affiliates product entities');
  }

}
