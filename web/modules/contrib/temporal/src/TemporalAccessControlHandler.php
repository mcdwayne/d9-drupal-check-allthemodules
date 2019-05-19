<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalAccessControlHandler.
 */

namespace Drupal\temporal;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Temporal entity.
 *
 * @see \Drupal\temporal\Entity\Temporal.
 */
class TemporalAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\temporal\TemporalInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished temporal entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published temporal entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit temporal entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete temporal entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add temporal entities');
  }

}
