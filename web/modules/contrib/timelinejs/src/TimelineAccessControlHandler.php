<?php

namespace Drupal\timelinejs;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Timeline entity.
 *
 * @see \Drupal\timelinejs\Entity\Timeline.
 */
class TimelineAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\timelinejs\Entity\TimelineInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished timeline entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published timeline entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit timeline entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete timeline entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add timeline entities');
  }

}
