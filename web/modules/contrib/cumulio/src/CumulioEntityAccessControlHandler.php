<?php

namespace Drupal\cumulio;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Cumulio entity entity.
 *
 * @see \Drupal\cumulio\Entity\CumulioEntity.
 */
class CumulioEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\cumulio\Entity\CumulioEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished cumulio entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published cumulio entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit cumulio entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete cumulio entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add cumulio entity entities');
  }

}
