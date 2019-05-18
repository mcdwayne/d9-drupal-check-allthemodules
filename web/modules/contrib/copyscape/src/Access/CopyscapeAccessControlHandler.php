<?php

namespace Drupal\copyscape\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Copyscape entities.
 *
 */
class CopyscapeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer copyscape entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }
}
