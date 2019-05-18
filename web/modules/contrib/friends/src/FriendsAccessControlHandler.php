<?php

namespace Drupal\friends;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Friends entity.
 *
 * @todo implement accessControl
 *
 * @see \Drupal\friends\Entity\Friends.
 */
class FriendsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\activity_creator\ActivityInterface $entity */
    switch ($operation) {
      case 'view':
        if ($account->id() == $entity->getRecipientId()) {
          return AccessResult::allowed();
        }

        if ($account->id() == $entity->getOwnerId()) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'view all published friends entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit friends entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete friends entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

}
