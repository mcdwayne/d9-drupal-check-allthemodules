<?php

namespace Drupal\ipfs_backend;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the IPFSHash entity.
 *
 * @see \Drupal\ipfs_backend\Entity\IPFSHash.
 */
class IPFSHashAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ipfs_backend\Entity\IPFSHashInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ipfshash entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published ipfshash entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ipfshash entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ipfshash entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ipfshash entities');
  }

}
