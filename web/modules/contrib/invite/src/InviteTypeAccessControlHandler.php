<?php

namespace Drupal\invite;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Invite type entity.
 *
 * @see \Drupal\invite\Entity\InviteType.
 */
class InviteTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\invite\InviteTypeInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished invite type entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published invite type entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit invite type entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete invite type entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add invite type entities');
  }

}
