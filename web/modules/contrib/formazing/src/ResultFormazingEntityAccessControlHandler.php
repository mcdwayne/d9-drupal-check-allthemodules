<?php

namespace Drupal\formazing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Result formazing entity entity.
 *
 * @see \Drupal\formazing\Entity\ResultFormazingEntity.
 */
class ResultFormazingEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity, $operation, AccountInterface $account
  ) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished result formazing entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published result formazing entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit result formazing entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete result formazing entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(
    AccountInterface $account, array $context, $entity_bundle = NULL
  ) {
    return AccessResult::allowedIfHasPermission($account, 'add result formazing entity entities');
  }

}
