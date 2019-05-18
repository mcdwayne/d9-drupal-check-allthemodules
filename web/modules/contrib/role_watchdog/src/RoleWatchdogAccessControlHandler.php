<?php

namespace Drupal\role_watchdog;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Role Watchdog entity.
 *
 * @see \Drupal\role_watchdog\Entity\RoleWatchdog.
 */
class RoleWatchdogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\role_watchdog\Entity\RoleWatchdogInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished role watchdog entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published role watchdog entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit role watchdog entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete role watchdog entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add role watchdog entities');
  }

}
