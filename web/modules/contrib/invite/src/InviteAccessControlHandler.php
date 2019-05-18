<?php

namespace Drupal\invite;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Invite entity.
 *
 * @see \Drupal\invite\Entity\Invite.
 */
class InviteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\invite\InviteInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished invite entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published invite entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit invite entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete invite entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add invite entities');
  }

}
