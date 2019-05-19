<?php

namespace Drupal\user_attendance;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the media entity.
 */
class UserAttendanceAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer user attendance entities')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('view user attendance entities') && $entity->status->value);

      case 'update':
        return AccessResult::allowedIf($account->hasPermission('edit user attendance entities'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf($account->hasPermission('delete user attendance entities'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    // No opinion.
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add user attendance entities');
  }

}
