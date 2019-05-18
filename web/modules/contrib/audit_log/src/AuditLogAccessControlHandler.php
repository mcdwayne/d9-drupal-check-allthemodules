<?php

namespace Drupal\audit_log;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Audit log entity.
 *
 * @see \Drupal\audit_log\Entity\AuditLog.
 */
class AuditLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\audit_log\Entity\AuditLogInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view audit log entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit audit log entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete audit log entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add audit log entities');
  }

}
