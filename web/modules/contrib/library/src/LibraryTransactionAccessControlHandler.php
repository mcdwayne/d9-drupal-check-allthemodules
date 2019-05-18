<?php

namespace Drupal\library;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Library transaction entity.
 *
 * @see \Drupal\library\Entity\LibraryTransaction.
 */
class LibraryTransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\library\LibraryTransactionInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published library transaction entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit library transaction entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete library transaction entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add library transaction entities');
  }

}
