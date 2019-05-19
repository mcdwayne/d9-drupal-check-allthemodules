<?php

namespace Drupal\transcoding;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Transcoding job entity.
 *
 * @see \Drupal\transcoding\Entity\TranscodingJob.
 */
class TranscodingJobAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\transcoding\TranscodingJobInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published transcoding job entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit transcoding job entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete transcoding job entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add transcoding job entities');
  }

}
