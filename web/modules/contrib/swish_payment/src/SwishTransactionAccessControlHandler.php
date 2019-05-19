<?php

namespace Drupal\swish_payment;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Swish transaction entity.
 *
 * @see \Drupal\swish_payment\Entity\SwishTransaction.
 */
class SwishTransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\swish_payment\Entity\SwishTransactionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished swish transaction entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published swish transaction entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit swish transaction entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete swish transaction entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add swish transaction entities');
  }

}
