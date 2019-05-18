<?php

namespace Drupal\entity_log;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Entity log entity.
 *
 * @see \Drupal\entity_log\Entity\EntityLog.
 */
class EntityLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\entity_log\Entity\EntityLogInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished entity log entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published entity log entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit entity log entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete entity log entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add entity log entities');
  }

}
