<?php

namespace Drupal\dmt;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Weekly usage entity.
 *
 * @see \Drupal\dmt\Entity\WeeklyUsage.
 */
class WeeklyUsageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dmt\Entity\WeeklyUsageInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view weekly usage entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit weekly usage entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete weekly usage entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add weekly usage entities');
  }

}
