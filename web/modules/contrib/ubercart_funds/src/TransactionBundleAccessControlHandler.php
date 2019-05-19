<?php

namespace Drupal\ubercart_funds;

use Drupal\ubercart_funds\Entity\TransactionType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines a default implementation for entity access control handler.
 */
class TransactionBundleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Performs access checks.
   *
   * This method override the default AccessControlHandler to give
   * access to all users to transaction bundles on view operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result as neutral.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view' && $entity instanceof TransactionType) {
      return AccessResult::allowed();
    }
  }

}
