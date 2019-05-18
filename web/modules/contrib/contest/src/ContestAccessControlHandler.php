<?php

namespace Drupal\contest;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the contest entity.
 */
class ContestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Determine if the user has permission to perform this operation.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The contest entity.
   * @param string $operation
   *   The requested operation.
   * @param Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   True if the user has permission to perform the requested operation.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view' && $account->hasPermission('access contests')) {
      return AccessResult::allowedIfHasPermission($account, $account->hasPermission('access contests'));
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
