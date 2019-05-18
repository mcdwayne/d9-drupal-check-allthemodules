<?php

namespace Drupal\bookkeeping;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for bookkeeping accounts.
 */
class AccountAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);

    // We can't modify a forbidden, so return early.
    if ($access->isForbidden()) {
      return $access;
    }

    $permissions = [
      'manage bookkeeping',
    ];
    if ($operation == 'view') {
      $permissions[] = 'view bookkeeping';
    }
    return $access->orIf(AccessResult::allowedIfHasPermissions($account, $permissions, 'OR'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = parent::checkCreateAccess($account, $context, $entity_bundle);

    // We can't modify a forbidden, so return early.
    if ($access->isForbidden()) {
      return $access;
    }

    return $access->orIf(AccessResult::allowedIfHasPermission($account, 'manage bookkeeping'));
  }

}
