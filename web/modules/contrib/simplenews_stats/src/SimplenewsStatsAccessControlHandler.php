<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the simplenews stats entity type.
 */
class SimplenewsStatsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view simplenews stats');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit simplenews stats', 'administer simplenews stats'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete simplenews stats', 'administer simplenews stats'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create simplenews stats', 'administer simplenews stats'], 'OR');
  }

}
