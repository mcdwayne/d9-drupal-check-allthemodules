<?php

namespace Drupal\track_file_downloads;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the FileTracker entity.
 */
class FileTrackerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ((int) $account->id() === 1) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\track_file_downloads\Entity\FileTrackerInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view file tracker entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit file tracker entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete file tracker entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add file tracker entities');
  }

}
