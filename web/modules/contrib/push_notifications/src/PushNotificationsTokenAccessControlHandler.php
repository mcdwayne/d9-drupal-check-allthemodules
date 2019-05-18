<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsTokenAccessControlHandler.
 */

namespace Drupal\push_notifications;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the push notification token entity type.
 *
 * @see \Drupal\push_notifications\Entity\PushNotificationToken
 */
class PushNotificationsTokenAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['view device tokens', 'administer device tokens'], 'OR');
        break;

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['create device token', 'administer device tokens'], 'OR');
        break;

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete device tokens', 'administer device tokens'], 'OR');
        break;

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create device token');
  }

}
